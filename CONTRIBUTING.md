# Contributing to JTD Admin Panel

Thank you for considering contributing to the JTD Admin Panel! We welcome contributions from the community and are grateful for your support.

## 📋 Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Making Changes](#making-changes)
- [Testing](#testing)
- [Code Style](#code-style)
- [Submitting Changes](#submitting-changes)
- [Reporting Issues](#reporting-issues)

## 🤝 Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code. Please be respectful and constructive in all interactions.

## 🚀 Getting Started

### Prerequisites

- PHP 8.1 or higher
- Laravel 10.0 or higher
- Node.js 16.0 or higher
- Composer
- Git

### Fork and Clone

1. Fork the repository on GitHub
2. Clone your fork locally:
   ```bash
   git clone https://github.com/your-username/admin-panel.git
   cd admin-panel
   ```

## 🔧 Development Setup

### 1. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 2. Set Up Testing Environment

```bash
# Copy environment file
cp .env.example .env

# Run database migrations for testing
php artisan migrate --env=testing
```

### 3. Build Assets

```bash
# For development
npm run dev

# For production
npm run build
```

### 4. Run Tests

```bash
# Run all tests
composer test

# Run specific test file
composer test tests/Unit/ResourceTest.php

# Run with coverage
composer test-coverage
```

## 🛠 Making Changes

### Branch Naming

Use descriptive branch names:
- `feature/add-new-field-type`
- `bugfix/fix-resource-validation`
- `docs/update-installation-guide`

### Commit Messages

Follow conventional commit format:
- `feat: add new field type for currency`
- `fix: resolve resource validation issue`
- `docs: update installation instructions`
- `test: add tests for metric calculations`

### Code Organization

- **Controllers**: `src/Http/Controllers/`
- **Models**: `src/Models/`
- **Resources**: `src/Resources/`
- **Fields**: `src/Fields/`
- **Metrics**: `src/Metrics/`
- **Commands**: `src/Console/Commands/`
- **Tests**: `tests/`
- **Frontend**: `resources/js/`

## 🧪 Testing

### Writing Tests

- Write tests for all new functionality
- Maintain or improve test coverage
- Use descriptive test method names
- Follow the existing test structure

### Test Types

1. **Unit Tests**: Test individual classes and methods
2. **Feature Tests**: Test complete workflows
3. **Performance Tests**: Ensure response times meet targets

### Running Tests

```bash
# All tests
composer test

# Unit tests only
composer test tests/Unit/

# Feature tests only
composer test tests/Feature/

# Performance tests
composer test tests/Performance/
```

## 🎨 Code Style

### PHP Code Style

We follow PSR-12 coding standards with some additional rules:

- Use strict types: `declare(strict_types=1);`
- Use type hints for all parameters and return types
- Use meaningful variable and method names
- Add PHPDoc blocks for all public methods
- Keep methods focused and small

### Frontend Code Style

- Use Vue 3 Composition API
- Follow Tailwind CSS conventions
- Use TypeScript where possible
- Keep components small and focused
- Use proper prop validation

### Formatting

```bash
# Format PHP code
composer format

# Check PHP code style
composer check-style

# Format JavaScript/Vue code
npm run format

# Lint JavaScript/Vue code
npm run lint
```

## 📝 Submitting Changes

### Pull Request Process

1. **Create a branch** from `main`
2. **Make your changes** following the guidelines above
3. **Write or update tests** for your changes
4. **Run the test suite** to ensure everything passes
5. **Update documentation** if needed
6. **Submit a pull request** with a clear description

### Pull Request Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Tests pass locally
- [ ] New tests added for new functionality
- [ ] Manual testing completed

## Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] No breaking changes (or clearly documented)
```

## 🐛 Reporting Issues

### Before Reporting

1. Check existing issues to avoid duplicates
2. Test with the latest version
3. Gather relevant information

### Issue Template

```markdown
## Bug Description
Clear description of the bug

## Steps to Reproduce
1. Step one
2. Step two
3. Step three

## Expected Behavior
What should happen

## Actual Behavior
What actually happens

## Environment
- PHP Version:
- Laravel Version:
- Package Version:
- Browser (if applicable):

## Additional Context
Any other relevant information
```

## 🏷 Release Process

Releases follow semantic versioning (SemVer):
- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

## 📚 Documentation

When contributing:
- Update relevant documentation
- Add examples for new features
- Keep README.md current
- Update CHANGELOG.md

## 💡 Feature Requests

We welcome feature requests! Please:
1. Check existing issues first
2. Provide detailed use cases
3. Consider implementation complexity
4. Be open to discussion

## 🙏 Recognition

Contributors will be recognized in:
- CHANGELOG.md
- GitHub contributors list
- Package documentation

## 📞 Getting Help

- **GitHub Issues**: For bugs and feature requests
- **GitHub Discussions**: For questions and community support
- **Email**: jerthedev@gmail.com for security issues

## 📄 License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

Thank you for contributing to JTD Admin Panel! 🎉
