# Laravel Code Generator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mohsen-mhm/code-generator.svg?style=flat-square)](https://packagist.org/packages/mohsen-mhm/code-generator)
[![Total Downloads](https://img.shields.io/packagist/dt/mohsen-mhm/code-generator.svg?style=flat-square)](https://packagist.org/packages/mohsen-mhm/code-generator)
[![License](https://img.shields.io/packagist/l/mohsen-mhm/code-generator.svg?style=flat-square)](https://packagist.org/packages/mohsen-mhm/code-generator)

An advanced code generator for Laravel applications with Livewire 3 support. Rapidly build your Laravel applications by generating models, migrations, controllers, Livewire components, API resources, and tests with a single command.

## 🚀 Features

- **Complete CRUD Generation**: Generate all components needed for a feature with one command
- **Livewire 3 Support**: Full support for Livewire 3 components with proper form handling
- **Schema-Based Generation**: Define your database structure in a simple string format
- **API Ready**: Built-in support for API resources and API controllers
- **Testing Support**: Automatically generates both feature and unit tests
- **Customizable Templates**: All stubs can be published and customized
- **Comprehensive Components**:
  - Models with fillable attributes and casts
  - Database migrations
  - Controllers (web and API)
  - Livewire components with validation
  - API resources
  - Feature and unit tests

## 📋 Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher

## 📦 Installation

You can install the package via composer:

```bash
composer require mohsen-mhm/code-generator
```

The package will automatically register its service provider.

## ⚙️ Configuration

You can publish the configuration file with:

```bash
php artisan vendor:publish --tag=code-generator-config
```

This will create a `config/code-generator.php` file where you can customize the behavior of the generator.

If you want to customize the stub files, you can publish them with:

```bash
php artisan vendor:publish --tag=code-generator-stubs
```

## 🔧 Usage

### Basic Usage

Generate a complete CRUD setup for a model:

```bash
php artisan generate User --schema="name:string,email:string:unique,password:string" --all
```

This will generate:
- A User model
- A migration for the users table
- A UserController
- A UserResource for API responses
- A User Livewire component
- Tests for the User model

### Available Commands

#### Generate Everything

```bash
php artisan generate {name} --schema="field1:type1,field2:type2:modifier" --all
```

#### Generate Model

```bash
php artisan generate:model {name} --schema="field1:type1,field2:type2:modifier"
```

#### Generate Controller

```bash
php artisan generate:controller {name} --model=ModelName --api
```

#### Generate Livewire Component

```bash
php artisan generate:livewire {name} --schema="field1:type1,field2:type2:modifier" --model=ModelName
```

#### Generate Migration

```bash
php artisan generate:migration {name} --schema="field1:type1,field2:type2:modifier" --table=table_name
```

#### Generate API Resource

```bash
php artisan generate:resource {name} --schema="field1:type1,field2:type2:modifier" --collection
```

#### Generate Tests

```bash
php artisan generate:test {name} --model=ModelName --feature
```

### Schema Format

The schema format follows a simple pattern:

```
field_name:field_type:modifier1:modifier2
```

For example:

```
name:string,email:string:unique,age:integer:nullable,is_active:boolean:default:true
```

#### Available Field Types

- `string`
- `integer`, `bigInteger`, `tinyInteger`, `smallInteger`, `mediumInteger`
- `float`, `double`, `decimal`
- `boolean`
- `date`, `dateTime`, `time`, `timestamp`
- `text`, `mediumText`, `longText`
- `json`, `jsonb`
- And more standard Laravel migration column types

#### Available Modifiers

- `nullable`
- `unique`
- `index`
- `default:{value}`
- `comment:{text}`

## 🛠️ Configuration Options

### Namespace Configuration

```php
'namespace' => 'App',
```

### Path Configuration

```php
'paths' => [
    'models' => app_path('Models'),
    'controllers' => app_path('Http/Controllers'),
    'livewire' => app_path('Livewire'),
    'migrations' => database_path('migrations'),
    'resources' => app_path('Http/Resources'),
    'tests' => base_path('tests'),
],
```

### Model Options

```php
'models' => [
    'timestamps' => true,
    'soft_deletes' => false,
    'fillable' => true,
    'casts' => true,
    'relationships' => true,
],
```

### Livewire Options

```php
'livewire' => [
    'version' => 3,
    'include_tests' => true,
    'include_views' => true,
],
```

## 🎨 Customizing Stubs

After publishing the stubs, you can find them in `resources/stubs/vendor/code-generator`. Modify them to match your project's coding style and requirements.

## 📝 Examples

### Generate a Blog Post Model with All Components

```bash
php artisan generate Post --schema="title:string,content:text,published_at:timestamp:nullable,user_id:foreignId" --all
```

### Generate a Product API

```bash
php artisan generate Product --schema="name:string,description:text,price:decimal:8,2,stock:integer,category_id:foreignId" --api --all
```

### Generate a Livewire Component for Comments

```bash
php artisan generate:livewire CommentManager --schema="content:text,user_id:foreignId,post_id:foreignId" --model=Comment
```

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## 🙏 Credits

- [Mohsen](https://github.com/mohsen-mhm)
- [All Contributors](../../contributors)

This package is inspired by the need to rapidly scaffold Laravel applications with modern best practices.


