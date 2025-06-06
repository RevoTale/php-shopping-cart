<?php

declare(strict_types=1);

namespace RevoTale\ShoppingCart;


use DomainException;
use InvalidArgumentException;
use UnexpectedValueException;
use function bcadd;
use function bccomp;
use function bcdiv;
use function bcmul;
use function bcpow;
use function bcsqrt;
use function bcsub;
use function ceil;
use function fmod;
use function is_float;
use function is_infinite;
use function is_int;
use function is_nan;
use function is_string;
use function log;
use function log10;
use function max;
use function min;
use function number_format;
use function preg_match;
use function str_pad;
use function strlen;
use function strpos;
use function substr;

/**
 * Immutable object that represents a rational number
 */
class Decimal
{
    public const DEFAULT_SCALE = 16;

    public const CLASSIC_DECIMAL_NUMBER_REGEXP = '/^([+\-]?)0*(([1-9]\d*|\d)(\.\d+)?)$/';

    public const EXP_NOTATION_NUMBER_REGEXP = '/^ (?P<sign> [+\-]?) 0*(?P<mantissa> [0-9](?P<decimals> \.[0-9]+)?) [eE] (?P<expSign> [+\-]?)(?P<exp> \d+)$/x';

    public const EXP_NUM_GROUPS_NUMBER_REGEXP = '/^ (?P<int> \d*) (?: \. (?P<dec> \d+) ) E (?P<sign>[\+\-]) (?P<exp>\d+) $/x';



    /**
     * @param numeric-string $value Internal numeric value
     * @param int $scale Number of digits behind the point
     */
    private function __construct(protected string $value, private int $scale) {}

    /**
     * Decimal "constructor".
     */
    public static function create(int|float|string|self $value, ?int $scale = null): Decimal
    {
        if (is_int($value)) {
            return self::fromInteger($value);
        }

        if (is_float($value)) {
            return self::fromFloat($value, $scale);
        }

        if (is_string($value)) {
            return self::fromString($value, $scale);
        }

        return self::fromDecimal($value, $scale);
    }

    public static function fromInteger(int $intValue): Decimal
    {
        self::paramsValidation($intValue, null);

        return new self((string)$intValue, 0);
    }

    public static function fromFloat(float $fltValue, ?int $scale = null): Decimal
    {
        self::paramsValidation($fltValue, $scale);

        if (is_infinite($fltValue)) {
            throw new UnexpectedValueException('fltValue must be a finite number');
        }

        if (is_nan($fltValue)) {
            throw new UnexpectedValueException("fltValue can't be NaN");
        }

        $strValue = (string)$fltValue;
        $hasPoint = (str_contains($strValue, '.'));
        $result = preg_match(self::EXP_NUM_GROUPS_NUMBER_REGEXP, $strValue, $capture);
        if ($result !== false && $result !== 0) {
            if (null === $scale) {
                $sign = $capture['sign'];
                $scale = ('-' === ($sign))
                    ? $capture['exp'] + strlen($capture['dec'])
                    : self::DEFAULT_SCALE;
            }

            $strValue = number_format($fltValue, (int)$scale, '.', '');
        } else {
            $naturalScale = (
                strlen((string)fmod($fltValue, 1.0)) - 2 - (($fltValue < 0) ? 1 : 0) + ($hasPoint ? 0 : 1)
            );

            if (null === $scale) {
                $scale = $naturalScale;
            } else {
                $strValue .= ($hasPoint ? '' : '.') . str_pad('', $scale - $naturalScale, '0');
            }
        }

        assert(is_int($scale));
        assert(is_numeric($strValue));
        return new self($strValue, $scale);
    }

    public static function fromString(string $strValue, ?int $scale = null): Decimal
    {
        self::paramsValidation($strValue, $scale);

        if (preg_match(self::CLASSIC_DECIMAL_NUMBER_REGEXP, $strValue, $captures) === 1) {

            // Now it's time to strip leading zeros in order to normalize inner values
            $value = self::normalizeSign($captures[1]) . $captures[2];
            $min_scale = isset($captures[4]) ? max(0, strlen($captures[4]) - 1) : 0;
        } elseif (preg_match(self::EXP_NOTATION_NUMBER_REGEXP, $strValue, $captures) === 1) {
            $mantisa = $captures['mantissa'];
            assert(is_numeric($mantisa));
            [$min_scale, $value] = self::fromExpNotationString(
                $scale,
                $captures['sign'],
                $mantisa,
                strlen($captures['mantissa']) - 1,
                $captures['expSign'],
                (int)$captures['exp']
            );
        } else {
            throw new UnexpectedValueException('strValue must be a number');
        }

        $scale = $scale ?? $min_scale;
        if ($scale < $min_scale) {
            assert(is_numeric($value));
            $value = self::innerRound($value, $scale);
        } elseif ($min_scale < $scale) {
            assert(is_numeric($value));
            $hasPoint = (str_contains($value, '.'));
            $value .= ($hasPoint ? '' : '.') . str_pad('', $scale - $min_scale, '0');
        }

        assert(is_numeric($value));
        return new self($value, $scale);
    }

    /**
     * Constructs a new Decimal object based on a previous one,
     * but changing it's $scale property.
     */
    public static function fromDecimal(Decimal $decValue, ?int $scale = null): Decimal
    {
        self::paramsValidation($decValue, $scale);

        // This block protect us from unnecessary additional instances
        if ($scale === null || $scale >= $decValue->scale) {
            return $decValue;
        }

        return new self(
            self::innerRound($decValue->value, $scale),
            $scale
        );
    }

    /**
     * Adds two Decimal objects
     */
    public function add(Decimal $b, ?int $scale = null): Decimal
    {
        self::paramsValidation($b, $scale);

        return self::fromString(
            bcadd($this->value, $b->value, max($this->scale, $b->scale)),
            $scale
        );
    }

    /**
     * Subtracts two BigNumber objects
     */
    public function sub(Decimal $b, ?int $scale = null): self
    {
        self::paramsValidation($b, $scale);

        return self::fromString(
            bcsub($this->value, $b->value, max($this->scale, $b->scale)),
            $scale
        );
    }

    /**
     * Multiplies two BigNumber objects
     */
    public function mul(Decimal $b, ?int $scale = null): Decimal
    {
        self::paramsValidation($b, $scale);

        if ($b->isZero()) {
            return DecimalConstants::zero();
        }

        return self::fromString(
            bcmul($this->value, $b->value, $this->scale + $b->scale),
            $scale
        );
    }

    /**
     * Divides the object by $b .
     * Warning: div with $scale == 0 is not the same as
     *          integer division because it rounds the
     *          last digit in order to minimize the error.
     */
    public function div(Decimal $b, ?int $scale = null): Decimal
    {
        self::paramsValidation($b, $scale);

        if ($b->isZero()) {
            throw new DomainException("Division by zero is not allowed.");
        }

        if ($this->isZero()) {
            return DecimalConstants::zero();
        }

        if (null !== $scale) {
            $divscale = $scale;
        } else {
            // $divscale is calculated in order to maintain a reasonable precision
            $this_abs = $this->abs();
            $b_abs = $b->abs();

            $log10_result =
                $this->innerLog10($this_abs->value, $this_abs->scale, 1) -
                $this->innerLog10($b_abs->value, $b_abs->scale, 1);

            $divscale = (int)max(
                $this->scale + $b->scale,
                max(
                    $this->countSignificativeDigits($this, $this_abs),
                    $this->countSignificativeDigits($b, $b_abs)
                ) - max(ceil((float)$log10_result), 0),
                ceil(-$log10_result) + 1
            );
        }

        return self::fromString(
            bcdiv($this->value, $b->value, $divscale + 1),
            $divscale
        );
    }

    /**
     * Returns the square root of this object
     */
    public function sqrt(?int $scale = null): Decimal
    {
        if ($this->isNegative()) {
            throw new DomainException(
                "Decimal can't handle square roots of negative numbers (it's only for real numbers)."
            );
        }

        if ($this->isZero()) {
            return DecimalConstants::zero();
        }

        $sqrt_scale = ($scale ?? $this->scale);

        return self::fromString(
            bcsqrt($this->value, $sqrt_scale + 1),
            $sqrt_scale
        );
    }

    /**
     * Powers this value to $b
     *
     * @param Decimal $b exponent
     */
    public function pow(Decimal $b, ?int $scale = null): Decimal
    {
        if ($this->isZero()) {
            if ($b->isPositive()) {
                return self::fromDecimal($this, $scale);
            }

            throw new DomainException("zero can't be powered to zero or negative numbers.");
        }

        if ($b->isZero()) {
            return DecimalConstants::one();
        }

        if ($b->isNegative()) {
            return DecimalConstants::one()->div(
                $this->pow($b->additiveInverse(), max($scale, self::DEFAULT_SCALE)),
                max($scale, self::DEFAULT_SCALE)
            );
        }

        if (0 === $b->scale) {
            $pow_scale = max($this->scale, $b->scale, $scale ?? 0);

            return self::fromString(
                bcpow($this->value, $b->value, $pow_scale + 1),
                $pow_scale
            );
        }

        if ($this->isPositive()) {
            $pow_scale = max($this->scale, $b->scale, $scale ?? 0);

            $truncated_b = bcadd($b->value, '0', 0);
            $remaining_b = bcsub($b->value, $truncated_b, $b->scale);

            $first_pow_approx = bcpow($this->value, $truncated_b, $pow_scale + 1);
            $intermediate_root = $this->innerPowWithLittleExponent($this->value, $remaining_b, $b->scale, $pow_scale + 1);

            $result = bcmul($first_pow_approx, $intermediate_root, $pow_scale + 1);
            return self::fromString(
                $result,
                $pow_scale
            );
        }

        // elseif ($this->isNegative())
        if (!$b->isInteger()) {
            throw new UnexpectedValueException(
                "Usually negative numbers can't be powered to non integer numbers. " .
                    "The cases where is possible are not implemented."
            );
        }

        return (preg_match('/^[+\-]?\d*[02468](\.0+)?$/', $b->value, $captures) === 1)
            ? $this->additiveInverse()->pow($b, $scale)                      // $b is an even number
            : $this->additiveInverse()->pow($b, $scale)->additiveInverse();  // $b is an odd number
    }

    /**
     * Returns the object's logarithm in base 10
     */
    public function log10(?int $scale = null): Decimal
    {
        if ($this->isNegative()) {
            throw new DomainException(
                "Decimal can't handle logarithms of negative numbers (it's only for real numbers)."
            );
        }

        if ($this->isZero()) {
            throw new DomainException(
                "Decimal can't represent infinite numbers."
            );
        }

        return self::fromString(
            $this->innerLog10($this->value, $this->scale, $scale !== null ? $scale + 1 : $this->scale + 1),
            $scale
        );
    }

    public function isZero(?int $scale = null): bool
    {
        $cmp_scale = $scale ?? $this->scale;

        return (bccomp(self::innerRound($this->value, $cmp_scale), '0', $cmp_scale) === 0);
    }

    public function isPositive(): bool
    {
        return ($this->value[0] !== '-' && !$this->isZero());
    }

    public function isNegative(): bool
    {
        return ($this->value[0] === '-');
    }

    public function isInteger(): bool
    {
        return (preg_match('/^[+\-]?\d+(\.0+)?$/', $this->value, $captures) === 1);
    }

    /**
     * Equality comparison between this object and $b
     */
    public function equals(Decimal $b, ?int $scale = null): bool
    {
        self::paramsValidation($b, $scale);

        if ($this === $b) {
            return true;
        }

        $cmp_scale = $scale ?? max($this->scale, $b->scale);

        return (
            bccomp(
                self::innerRound($this->value, $cmp_scale),
                self::innerRound($b->value, $cmp_scale),
                $cmp_scale
            ) === 0
        );
    }

    /**
     * $this > $b : returns 1 , $this < $b : returns -1 , $this == $b : returns 0
     */
    public function comp(Decimal $b, ?int $scale = null): int
    {
        self::paramsValidation($b, $scale);

        if ($this === $b) {
            return 0;
        }

        $cmp_scale = $scale ?? max($this->scale, $b->scale);

        return bccomp(
            self::innerRound($this->value, $cmp_scale),
            self::innerRound($b->value, $cmp_scale),
            $cmp_scale
        );
    }


    /**
     * Returns true if $this > $b, otherwise false
     */
    public function isGreaterThan(Decimal $b, ?int $scale = null): bool
    {
        return $this->comp($b, $scale) === 1;
    }

    /**
     * Returns true if $this >= $b
     */
    public function isGreaterOrEqualTo(Decimal $b, ?int $scale = null): bool
    {
        $comparisonResult = $this->comp($b, $scale);

        return $comparisonResult === 1 || $comparisonResult === 0;
    }

    /**
     * Returns true if $this < $b, otherwise false
     */
    public function isLessThan(Decimal $b, ?int $scale = null): bool
    {
        return $this->comp($b, $scale) === -1;
    }

    /**
     * Returns true if $this <= $b, otherwise false
     */
    public function isLessOrEqualTo(Decimal $b, ?int $scale = null): bool
    {
        $comparisonResult = $this->comp($b, $scale);

        return $comparisonResult === -1 || $comparisonResult === 0;
    }

    /**
     * Returns the element's additive inverse.
     */
    public function additiveInverse(): Decimal
    {
        if ($this->isZero()) {
            return $this;
        }

        $value = $this->isNegative() ? substr($this->value, 1) : '-' . $this->value;

        assert(is_numeric($value));
        return new self($value, $this->scale);
    }


    /**
     * "Rounds" the Decimal to have at most $scale digits after the point
     */
    public function round(int $scale = 0): Decimal
    {
        if ($scale >= $this->scale) {
            return $this;
        }

        return self::fromString(self::innerRound($this->value, $scale));
    }

    /**
     * "Ceils" the Decimal to have at most $scale digits after the point
     * @param integer $scale
     */
    public function ceil($scale = 0): Decimal
    {
        if ($scale >= $this->scale) {
            return $this;
        }

        if ($this->isNegative()) {
            return self::fromString(bcadd($this->value, '0', $scale));
        }

        return $this->innerTruncate($scale);
    }

    private function innerTruncate(int $scale = 0, bool $ceil = true): Decimal
    {
        $rounded = bcadd($this->value, '0', $scale);

        $rlen = strlen($rounded);
        $tlen = strlen($this->value);

        $mustTruncate = false;
        for ($i = $tlen - 1; $i >= $rlen; --$i) {
            if ((int)$this->value[$i] > 0) {
                $mustTruncate = true;
                break;
            }
        }

        if ($mustTruncate) {
            $rounded = $ceil
                ? bcadd($rounded, bcpow('10', (string)-$scale, $scale), $scale)
                : bcsub($rounded, bcpow('10', (string)-$scale, $scale), $scale);
        }

        return self::fromString($rounded, $scale);
    }

    /**
     * "Floors" the Decimal to have at most $scale digits after the point
     */
    public function floor(int $scale = 0): Decimal
    {
        if ($scale >= $this->scale) {
            return $this;
        }

        if ($this->isNegative()) {
            return $this->innerTruncate($scale, false);
        }

        return self::fromString(bcadd($this->value, '0', $scale));
    }

    /**
     * Returns the absolute value (always a positive number)
     */
    public function abs(): Decimal
    {
        return ($this->isZero() || $this->isPositive())
            ? $this
            : $this->additiveInverse();
    }

    /**
     * Calculate modulo with a decimal
     * @return Decimal % $d
     */
    public function mod(Decimal $d, ?int $scale = null): self
    {
        $div = $this->div($d, 1)->floor();
        return $this->sub($div->mul($d), $scale);
    }

    /**
     * Calculates the sine of this method with the highest possible accuracy
     * Note that accuracy is limited by the accuracy of predefined PI;
     *
     * @return Decimal sin($this)
     */
    public function sin(?int $scale = null): Decimal
    {
        // First normalise the number in the [0, 2PI] domain
        $x = $this->mod(DecimalConstants::pi()->mul(self::fromString("2")));

        // PI has only 32 significant numbers
        $scale = $scale ?? 32;

        return $this->factorialSerie($x, DecimalConstants::zero(), static function ($i): \RevoTale\ShoppingCart\Decimal {
            return ($i % 2 === 1) ? (
                ($i % 4 === 1) ? DecimalConstants::one() : DecimalConstants::negativeOne()
            ) : DecimalConstants::zero();
        }, $scale);
    }

    /**
     * Calculates the cosecant of this with the highest possible accuracy
     * Note that accuracy is limited by the accuracy of predefined PI;
     */
    public function cosec(int $scale): Decimal
    {
        $sin = $this->sin($scale + 2);
        if ($sin->isZero()) {
            throw new DomainException(
                "The cosecant of this 'angle' is undefined."
            );
        }

        return DecimalConstants::one()->div($sin)->round($scale);
    }

    /**
     * Calculates the cosine of this method with the highest possible accuracy
     * Note that accuracy is limited by the accuracy of predefined PI;
     *
     * @return Decimal cos($this)
     */
    public function cos(?int $scale = null): Decimal
    {
        // First normalise the number in the [0, 2PI] domain
        $x = $this->mod(DecimalConstants::pi()->mul(self::fromString("2")));

        // PI has only 32 significant numbers
        $scale = $scale ?? 32;

        return $this->factorialSerie($x, DecimalConstants::one(), function ($i): \RevoTale\ShoppingCart\Decimal {
            return ($i % 2 === 0) ? (
                ($i % 4 === 0) ? DecimalConstants::one() : DecimalConstants::negativeOne()
            ) : DecimalConstants::zero();
        }, $scale);
    }

    /**
     * Calculates the secant of this with the highest possible accuracy
     * Note that accuracy is limited by the accuracy of predefined PI;
     */
    public function sec(int $scale): Decimal
    {
        $cos = $this->cos($scale + 2);
        if ($cos->isZero()) {
            throw new DomainException(
                "The secant of this 'angle' is undefined."
            );
        }

        return DecimalConstants::one()->div($cos)->round($scale);
    }

    /**
     *    Calculates the arcsine of this with the highest possible accuracy
     */
    public function arcsin(int $scale): Decimal
    {
        if ($this->comp(DecimalConstants::one(), $scale + 2) === 1 || $this->comp(DecimalConstants::negativeOne(), $scale + 2) === -1) {
            throw new DomainException(
                "The arcsin of this number is undefined."
            );
        }

        if ($this->round($scale)->isZero()) {
            return DecimalConstants::zero();
        }

        if ($this->round($scale)->equals(DecimalConstants::one())) {
            return DecimalConstants::pi()->div(self::fromInteger(2))->round($scale);
        }

        if ($this->round($scale)->equals(DecimalConstants::negativeOne())) {
            return DecimalConstants::pi()->div(self::fromInteger(-2))->round($scale);
        }


        return $this->powerSerie($this, DecimalConstants::zero(), $scale);
    }

    /**
     *    Calculates the arccosine of this with the highest possible accuracy
     */
    public function arccos(int $scale): Decimal
    {
        if ($this->comp(DecimalConstants::one(), $scale + 2) === 1 || $this->comp(DecimalConstants::negativeOne(), $scale + 2) === -1) {
            throw new DomainException(
                "The arccos of this number is undefined."
            );
        }

        $piOverTwo = DecimalConstants::pi()->div(self::fromInteger(2), $scale + 2)->round($scale);

        if ($this->round($scale)->isZero()) {
            return $piOverTwo;
        }

        if ($this->round($scale)->equals(DecimalConstants::one())) {
            return DecimalConstants::zero();
        }

        if ($this->round($scale)->equals(DecimalConstants::negativeOne())) {
            return DecimalConstants::pi()->round($scale);
        }


        return $piOverTwo->sub(
            $this->powerSerie($this, DecimalConstants::zero(), $scale)
        )->round($scale);
    }

    /**
     *    Calculates the arctangente of this with the highest possible accuracy
     */
    public function arctan(int $scale): Decimal
    {
        $piOverFour = DecimalConstants::pi()->div(self::fromInteger(4), $scale + 2)->round($scale);

        if ($this->round($scale)->isZero()) {
            return DecimalConstants::zero();
        }

        if ($this->round($scale)->equals(DecimalConstants::one())) {
            return $piOverFour;
        }

        if ($this->round($scale)->equals(DecimalConstants::negativeOne())) {
            return DecimalConstants::negativeOne()->mul($piOverFour);
        }


        return $this->simplePowerSerie($this, DecimalConstants::zero(), $scale + 2)->round($scale);
    }

    /**
     * Calculates the arccotangente of this with the highest possible accuracy
     */
    public function arccot(?int $scale = null): Decimal
    {
        $scale = $scale ?? 32;

        $piOverTwo = DecimalConstants::pi()->div(self::fromInteger(2), $scale + 2);
        if ($this->round($scale)->isZero()) {
            return $piOverTwo->round($scale);
        }

        $piOverFour = DecimalConstants::pi()->div(self::fromInteger(4), $scale + 2);
        if ($this->round($scale)->equals(DecimalConstants::one())) {
            return $piOverFour->round($scale);
        }

        if ($this->round($scale)->equals(DecimalConstants::negativeOne())) {
            return DecimalConstants::negativeOne()->mul($piOverFour, $scale + 2)->round($scale);
        }

        return $piOverTwo->sub(
            $this->simplePowerSerie($this, DecimalConstants::zero(), $scale + 2)
        )->round($scale);
    }

    /**
     * Calculates the arcsecant of this with the highest possible accuracy
     */
    public function arcsec(int $scale): Decimal
    {
        if ($this->comp(DecimalConstants::one(), $scale + 2) === -1 && $this->comp(DecimalConstants::negativeOne(), $scale + 2) === 1) {
            throw new DomainException(
                "The arcsecant of this number is undefined."
            );
        }

        $piOverTwo = DecimalConstants::pi()->div(self::fromInteger(2), $scale + 2)->round($scale);

        if ($this->round($scale)->equals(DecimalConstants::one())) {
            return DecimalConstants::zero();
        }

        if ($this->round($scale)->equals(DecimalConstants::negativeOne())) {
            return DecimalConstants::pi()->round($scale);
        }

        return $piOverTwo->sub(
            $this->powerSerie(DecimalConstants::one()->div($this, $scale + 2), DecimalConstants::zero(), $scale + 2)
        )->round($scale);
    }

    /**
     * Calculates the arccosecant of this with the highest possible accuracy
     */
    public function arccsc(?int $scale = null): Decimal
    {
        if ($this->comp(DecimalConstants::one(), ($scale ?? 0) + 2) === -1 && $this->comp(DecimalConstants::negativeOne(), ($scale ?? 0) + 2) === 1) {
            throw new DomainException(
                "The arccosecant of this number is undefined."
            );
        }

        $scale = $scale ?? 32;

        if ($this->round($scale)->equals(DecimalConstants::one())) {
            return DecimalConstants::pi()->div(self::fromInteger(2), $scale + 2)->round($scale);
        }

        if ($this->round($scale)->equals(DecimalConstants::negativeOne())) {
            return DecimalConstants::pi()->div(self::fromInteger(-2), $scale + 2)->round($scale);
        }

        return $this->powerSerie(DecimalConstants::one()->div($this, $scale + 2), DecimalConstants::zero(), $scale + 2)->round($scale);
    }

    /**
     * Returns exp($this), said in other words: e^$this .
     */
    public function exp(?int $scale = null): Decimal
    {
        if ($this->isZero()) {
            return DecimalConstants::one();
        }

        $scale = $scale ?? max(
            $this->scale,
            (int)($this->isNegative() ? $this->innerLog10($this->value, $this->scale, 0) : self::DEFAULT_SCALE)
        );

        return $this->factorialSerie($this, DecimalConstants::one(), function ($i): \RevoTale\ShoppingCart\Decimal {
            return DecimalConstants::one();
        }, $scale);
    }

    /**
     * Internal method used to compute sin, cos and exp
     * @param callable(int $generalTerm):Decimal $generalTerm
     */
    private function factorialSerie(Decimal $x, Decimal $firstTerm, callable $generalTerm, int $scale): Decimal
    {
        $approx = $firstTerm;
        $change = DecimalConstants::one();

        $faculty = DecimalConstants::one();    // Calculates the faculty under the sign
        $xPowerN = DecimalConstants::one();    // Calculates x^n

        for ($i = 1; !$change->floor($scale + 1)->isZero(); ++$i) {
            // update x^n and n! for this walkthrough
            $xPowerN = $xPowerN->mul($x);
            $faculty = $faculty->mul(self::fromInteger($i));

            /** @var Decimal $multiplier */
            $multiplier = $generalTerm($i);

            if (!$multiplier->isZero()) {
                $change = $multiplier->mul($xPowerN, $scale + 2)->div($faculty, $scale + 2);
                $approx = $approx->add($change, $scale + 2);
            }
        }

        return $approx->round($scale);
    }


    /**
     * Internal method used to compute arcsine and arcosine
     */
    private function powerSerie(Decimal $x, Decimal $firstTerm, int $scale): Decimal
    {
        $approx = $firstTerm;
        $change = DecimalConstants::one();

        $xPowerN = DecimalConstants::one();     // Calculates x^n
        $factorN = DecimalConstants::one();      // Calculates a_n

        $numerator = DecimalConstants::one();
        $denominator = DecimalConstants::one();

        for ($i = 1; !$change->floor($scale + 2)->isZero(); ++$i) {
            $xPowerN = $xPowerN->mul($x);

            if ($i % 2 === 0) {
                $factorN = DecimalConstants::zero();
            } elseif ($i === 1) {
                $factorN = DecimalConstants::one();
            } else {
                $incrementNum = self::fromInteger($i - 2);
                $numerator = $numerator->mul($incrementNum, $scale + 2);

                $incrementDen = self::fromInteger($i - 1);
                $increment = self::fromInteger($i);
                $denominator = $denominator
                    ->div($incrementNum, $scale + 2)
                    ->mul($incrementDen, $scale + 2)
                    ->mul($increment, $scale + 2);

                $factorN = $numerator->div($denominator, $scale + 2);
            }

            if (!$factorN->isZero()) {
                $change = $factorN->mul($xPowerN, $scale + 2);
                $approx = $approx->add($change, $scale + 2);
            }
        }

        return $approx->round($scale);
    }

    /**
     * Internal method used to compute arctan and arccotan
     */
    private function simplePowerSerie(Decimal $x, Decimal $firstTerm, int $scale): Decimal
    {
        $approx = $firstTerm;
        $change = DecimalConstants::one();

        $xPowerN = DecimalConstants::one();     // Calculates x^n
        DecimalConstants::one();      // Calculates a_n

        for ($i = 1; !$change->floor($scale + 2)->isZero(); ++$i) {
            $xPowerN = $xPowerN->mul($x);

            if ($i % 2 === 0) {
                $factorN = DecimalConstants::zero();
            } elseif ($i % 4 === 1) {
                $factorN = DecimalConstants::one()->div(self::fromInteger($i), $scale + 2);
            } else {
                $factorN = DecimalConstants::negativeOne()->div(self::fromInteger($i), $scale + 2);
            }

            if (!$factorN->isZero()) {
                $change = $factorN->mul($xPowerN, $scale + 2);
                $approx = $approx->add($change, $scale + 2);
            }
        }

        return $approx->round($scale);
    }

    /**
     * Calculates the tangent of this method with the highest possible accuracy
     * Note that accuracy is limited by the accuracy of predefined PI;
     */
    public function tan(int $scale = 0): Decimal
    {
        $cos = $this->cos($scale + 2);
        if ($cos->isZero()) {
            throw new DomainException(
                "The tangent of this 'angle' is undefined."
            );
        }

        return $this->sin($scale + 2)->div($cos)->round($scale);
    }

    /**
     * Calculates the cotangent of this method with the highest possible accuracy
     * Note that accuracy is limited by the accuracy of predefined PI;
     *
     * @return Decimal cotan($this)
     */
    public function cotan(int $scale = 0): Decimal
    {
        $sin = $this->sin($scale + 2);
        if ($sin->isZero()) {
            throw new DomainException(
                "The cotangent of this 'angle' is undefined."
            );
        }

        return $this->cos($scale + 2)->div($sin)->round($scale);
    }

    /**
     * Indicates if the passed parameter has the same sign as the method's bound object.
     */
    public function hasSameSign(Decimal $b): bool
    {
        return ($this->isPositive() && $b->isPositive()) || ($this->isNegative() && $b->isNegative());
    }

    public function asFloat(): float
    {
        return (float)$this->value;
    }

    public function asInteger(): int
    {
        return (int)$this->value;
    }

    /**
     * WARNING: use with caution! Return the inner representation of the class.
     */
    public function innerValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @param numeric-string $mantissa
     * @return array{int,numeric-string}
     */
    private static function fromExpNotationString(
        ?int   $scale,
        string $sign,
        string $mantissa,
        int    $nDecimals,
        string $expSign,
        int    $expVal
    ): array {
        $mantissaScale = max($nDecimals, 0);

        if (self::normalizeSign($expSign) === '') {
            $minScale = max($mantissaScale - $expVal, 0);
            $tmp_multiplier = bcpow('10', (string)$expVal);
        } else {
            $minScale = $mantissaScale + $expVal;
            $tmp_multiplier = bcpow('10', (string)-$expVal, $expVal);
        }

        $value = (
            self::normalizeSign($sign) .
            bcmul(
                $mantissa,
                $tmp_multiplier,
                max($minScale, $scale ?? 0)
            )
        );
        assert(is_numeric($value));

        return [$minScale, $value];
    }

    /**
     * "Rounds" the decimal string to have at most $scale digits after the point
     *
     * @param numeric-string $value
     * @return numeric-string
     */
    private static function innerRound(string $value, int $scale = 0): string
    {
        $rounded = bcadd($value, '0', $scale);

        $diffDigit = bcsub($value, $rounded, $scale + 1);
        $diffDigit = (int)$diffDigit[strlen($diffDigit) - 1];

        if ($diffDigit >= 5) {
            $rounded = ($value[0] !== '-')
                ? bcadd($rounded, bcpow('10', (string)-$scale, $scale), $scale)
                : bcsub($rounded, bcpow('10', (string)-$scale, $scale), $scale);
        }

        return $rounded;
    }

    /**
     * Calculates the logarithm (in base 10) of $value
     *
     * @param numeric-string $value The number we want to calculate its logarithm (only positive numbers)
     * @param int $in_scale Expected scale used by $value (only positive numbers)
     * @param int $out_scale Scale used by the return value (only positive numbers)
     * @return numeric-string
     */
    private function innerLog10(string $value, int $in_scale, int $out_scale): string
    {
        $value_len = strlen($value);

        $cmp = bccomp($value, '1', $in_scale);

        switch ($cmp) {
            case 1:
                $value_log10_approx = $value_len - ($in_scale > 0 ? ($in_scale + 2) : 1);
                $value_log10_approx = max(0, $value_log10_approx);

                return bcadd(
                    (string)$value_log10_approx,
                    (string)log10((float)bcdiv(
                        $value,
                        bcpow('10', (string)$value_log10_approx),
                        min($value_len, $out_scale)
                    )),
                    $out_scale
                );
            case -1:
                preg_match('/^0*\.(0*)[1-9]\d*$/', $value, $captures);
                $value_log10_approx = -strlen($captures[1] ?? '') - 1;

                return bcadd(
                    (string)$value_log10_approx,
                    (string)log10((float)bcmul(
                        $value,
                        bcpow('10', (string)-$value_log10_approx),
                        $in_scale + $value_log10_approx
                    )),
                    $out_scale
                );
            default: // case 0:
                return '0';
        }
    }

    /**
     * Returns $base^$exponent
     * @param numeric-string $base
     * @param numeric-string $exponent
     * @return numeric-string
     */
    private function innerPowWithLittleExponent(
        string $base,
        string $exponent,
        int    $exp_scale,
        int    $out_scale
    ): string {
        $inner_scale = (int)ceil($exp_scale * log(10) / log(2)) + 1;
        /**
         * @var numeric-string $result_a
         */
        $result_a = '1';
        /**
         * @var numeric-string $result_b
         */
        $result_b = '0';
        /**
         * @var numeric-string $zeroStr
         */
        $zeroStr = '0';

        $actual_index = 0;
        /**
         * @var numeric-string $exponent_remaining
         */
        $exponent_remaining = $exponent;

        while (
            bccomp($result_a, $result_b, $out_scale) !== 0
            && bccomp($exponent_remaining, $zeroStr, $inner_scale) !== 0
        ) {
            $result_b = $result_a;
            $index_info = $this->computeSquareIndex($exponent_remaining, $actual_index, $exp_scale, $inner_scale);
            $exponent_remaining = $index_info[1];
            assert(is_numeric($exponent_remaining));

            $result_a = bcmul(
                $result_a,
                $this->compute2NRoot($base, $index_info[0], 2 * ($out_scale + 1)),
                2 * ($out_scale + 1)
            );
        }

        return self::innerRound($result_a, $out_scale);
    }

    /**
     * Auxiliar method. It helps us to decompose the exponent into many summands.
     *
     * @param numeric-string $exponent_remaining
     * @param int $exp_scale Number of $exponent's significative digits
     * @param int $inner_scale ceil($exp_scale*log(10)/log(2))+1;
     * @return array{int,string}
     */
    private function computeSquareIndex(
        string $exponent_remaining,
        int    $actual_index,
        int    $exp_scale,
        int    $inner_scale
    ): array {
        $actual_rt = bcpow('0.5', (string)$actual_index, $exp_scale);
        $r = bcsub($exponent_remaining, $actual_rt, $inner_scale);

        while (bccomp($r, '0', $exp_scale) === -1) {
            ++$actual_index;
            $actual_rt = bcmul('0.5', $actual_rt, $inner_scale);
            $r = bcsub($exponent_remaining, $actual_rt, $inner_scale);
        }

        return [$actual_index, $r];
    }

    /**
     * Auxiliar method. Computes $base^((1/2)^$index)
     * @param numeric-string $base
     * @return numeric-string
     */
    private function compute2NRoot(string $base, int $index, int $out_scale): string
    {
        $result = $base;

        for ($i = 0; $i < $index; ++$i) {
            $result = bcsqrt($result, ($out_scale + 1) * ($index - $i) + 1);
        }

        return self::innerRound($result, $out_scale);
    }

    /**
     * Validates basic constructor's arguments
     */
    protected static function paramsValidation(mixed $value, ?int $scale = null): void
    {
        if (null === $value) {
            throw new InvalidArgumentException('$value must be a non null number');
        }

        if (null !== $scale && $scale < 0) {
            throw new InvalidArgumentException('$scale must be a positive integer');
        }
    }


    private static function normalizeSign(string $sign): string
    {
        if ('+' === $sign) {
            return '';
        }

        return $sign;
    }

    /**
     * Counts the number of significant digits of $val.
     * Assumes a consistent internal state (without zeros at the end or the start).
     */
    private function countSignificativeDigits(Decimal $val, Decimal $abs): int
    {
        return strlen($val->value) - (
            ($abs->comp(DecimalConstants::one()) === -1) ? 2 : max($val->scale, 1)
        ) - ($val->isNegative() ? 1 : 0);
    }
}
