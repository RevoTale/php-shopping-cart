<?php
declare(strict_types=1);

namespace RevoTale\ShoppingCart;

/**
 * Class that holds many important numeric constants
 */
final class DecimalConstants
{
    private static ?Decimal $ZERO = null;

    private static ?Decimal $ONE = null;

    private static ?Decimal $NEGATIVE_ONE = null;

    private static ?Decimal $PI = null;

    private static ?Decimal $EulerMascheroni = null;

    private static ?Decimal $GoldenRatio = null;

    private static ?Decimal $SilverRatio = null;

    private static ?Decimal $LightSpeed = null;

    private function __construct()
    {
    }

    public static function zero(): Decimal
    {
        if (!self::$ZERO instanceof \RevoTale\ShoppingCart\Decimal) {
            self::$ZERO = Decimal::fromInteger(0);
        }

        return self::$ZERO;
    }

    public static function one(): Decimal
    {
        if (!self::$ONE instanceof \RevoTale\ShoppingCart\Decimal) {
            self::$ONE = Decimal::fromInteger(1);
        }

        return self::$ONE;
    }

    public static function negativeOne(): Decimal
    {
        if (!self::$NEGATIVE_ONE instanceof \RevoTale\ShoppingCart\Decimal) {
            self::$NEGATIVE_ONE = Decimal::fromInteger(-1);
        }

        return self::$NEGATIVE_ONE;
    }

    /**
     * Returns the Pi number.
     */
    public static function pi(): Decimal
    {
        if (!self::$PI instanceof \RevoTale\ShoppingCart\Decimal) {
            self::$PI = Decimal::fromString(
                "3.14159265358979323846264338327950"
            );
        }

        return self::$PI;
    }

    /**
     * Returns the Euler's E number.
     */
    public static function e(int $scale = 32): Decimal
    {
        if ($scale < 0) {
            throw new \InvalidArgumentException("\$scale must be positive.");
        }

        return self::one()->exp($scale);
    }

    /**
     * Returns the Euler-Mascheroni constant.
     */
    public static function eulerMascheroni(): Decimal
    {
        if (!self::$EulerMascheroni instanceof \RevoTale\ShoppingCart\Decimal) {
            self::$EulerMascheroni = Decimal::fromString(
                "0.57721566490153286060651209008240"
            );
        }

        return self::$EulerMascheroni;
    }

    /**
     * Returns the Golden Ration, also named Phi.
     */
    public static function goldenRatio(): Decimal
    {
        if (!self::$GoldenRatio instanceof \RevoTale\ShoppingCart\Decimal) {
            self::$GoldenRatio = Decimal::fromString(
                "1.61803398874989484820458683436564"
            );
        }

        return self::$GoldenRatio;
    }

    /**
     * Returns the Silver Ratio.
     */
    public static function silverRatio(): Decimal
    {
        if (!self::$SilverRatio instanceof \RevoTale\ShoppingCart\Decimal) {
            self::$SilverRatio = Decimal::fromString(
                "2.41421356237309504880168872420970"
            );
        }

        return self::$SilverRatio;
    }

    /**
     * Returns the Light of Speed measured in meters / second.
     */
    public static function lightSpeed(): Decimal
    {
        if (!self::$LightSpeed instanceof \RevoTale\ShoppingCart\Decimal) {
            self::$LightSpeed = Decimal::fromInteger(299792458);
        }

        return self::$LightSpeed;
    }
}