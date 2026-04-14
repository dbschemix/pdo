# Database Migrator: PDO

### Static analysis

To run static analysis:
- [psalm](https://psalm.dev/)
- [phpstan](https://phpstan.org/)

```shell
make check
```

To fix code style:
- [phpcs](https://github.com/squizlabs/PHP_CodeSniffer)
- [rector](https://getrector.com/)

```shell
make fix
```

### Testing

The package is tested with 
- [PHPUnit](https://phpunit.de/)
- [Infection](https://github.com/infection/infection)

To run tests:
```shell
make tests
```
