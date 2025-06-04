# CI Workflow Summary

## Overview
A comprehensive GitHub Actions CI workflow has been implemented for the PHP Shopping Cart library, inspired by modern best practices from the RevoTale/next-scroll-restorer npm package.

## Workflow Features

### 🚀 **Modern CI Structure**
- **Concurrency Control**: Cancels previous workflow runs for the same branch
- **Matrix Strategy**: Tests on both PHP 8.2 (lowest) and PHP 8.4 (highest)
- **Proper Permissions**: Read-only permissions by default
- **Resource Management**: Timeout limits for all jobs (5-15 minutes)
- **Scheduled Runs**: Weekly automated testing on Mondays

### 🔧 **Jobs Overview**

#### 1. **Setup & Validation** (5 min timeout)
- Validates `composer.json` structure
- Checks for `composer.lock` existence
- Verifies required composer scripts are present
- Sets up the PHP version matrix for other jobs

#### 2. **PHPUnit Tests** (15 min timeout)
- Runs on PHP 8.2 and PHP 8.4
- Generates coverage report on PHP 8.2
- Uploads coverage to Codecov
- Artifacts uploaded on failure for debugging

#### 3. **PHPStan Static Analysis** (10 min timeout)
- Static analysis on both PHP versions
- Uses project's `phpstan.neon` configuration
- Artifacts uploaded on failure

#### 4. **Rector Code Quality Check** (10 min timeout)
- Runs Rector in dry-run mode to check for potential improvements
- Tests on both PHP versions
- Artifacts uploaded on failure

#### 5. **Coding Standards** (5 min timeout)
- Automatically detects if PHP CS Fixer is available
- Runs dry-run check if available
- Gracefully skips if not installed (optional job)

#### 6. **Finalization** (5 min timeout)
- Depends on all previous jobs
- Comprehensive status checking
- Success/failure reporting with detailed feedback
- "Ready to merge" confirmation when all tests pass

### 🎯 **Key Features Inspired by RevoTale**

1. **Separate Job per Test Type**: Each tool (PHPUnit, PHPStan, Rector) runs in its own job
2. **Matrix Strategy**: Tests on lowest and highest supported PHP versions
3. **Proper Caching**: Composer cache using official cache directory
4. **Artifact Management**: Upload logs and configs on failure for debugging
5. **Comprehensive Validation**: Pre-flight checks in setup job
6. **Modern Action Versions**: Uses latest GitHub Actions (v4)
7. **Resource Optimization**: Timeouts and concurrency control
8. **Professional Output**: Emoji-enhanced status messages

### 📊 **PHP Version Coverage**
- **PHP 8.2**: Lowest supported version (with coverage reporting)
- **PHP 8.4**: Highest supported version

### 🔄 **Trigger Events**
- **Push**: To `main` and `develop` branches
- **Pull Request**: To `main` and `develop` branches  
- **Schedule**: Weekly on Mondays at 2 AM UTC

### 📦 **Dependencies & Tools**
- **Required**: PHPUnit, PHPStan, Rector
- **Optional**: PHP CS Fixer (gracefully detected)
- **Coverage**: Codecov integration
- **Caching**: Composer packages

### ✅ **Quality Assurance**
- All jobs must pass for CI success
- Coding standards is optional but if present, must not fail
- Comprehensive error reporting and debugging artifacts
- Clear success/failure messaging

## Usage

The workflow automatically runs on:
- Every push to main/develop branches
- Every pull request to main/develop branches
- Weekly scheduled runs for dependency monitoring

### Local Testing
Before pushing, you can run the same checks locally:

```bash
# Run all quality checks
composer run-script phpunit
composer run-script phpstan  
composer run-script rector:test

# Optional: coding standards (if available)
composer run-script cs-fix -- --dry-run --diff
```

### CI Status
- ✅ **Green**: All tests pass, ready to merge
- ❌ **Red**: One or more quality checks failed
- 🟡 **Yellow**: Jobs still running

This CI workflow ensures code quality, compatibility, and reliability across supported PHP versions while providing clear feedback for developers.
