# Repositories

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sebastian-berc/repositories.svg?style=flat-square)](https://packagist.org/packages/sebastian-berc/repositories)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/SebastianBerc/Repositories/master.svg?style=flat-square)](https://travis-ci.org/SebastianBerc/Repositories)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/SebastianBerc/Repositories.svg?style=flat-square)](https://scrutinizer-ci.com/g/SebastianBerc/Repositories/code-structure)
[![SensioLabs Insight](https://img.shields.io/sensiolabs/i/168dab35-a889-4b1c-99e7-6e0e44f611f6.svg?style=flat-square)](https://insight.sensiolabs.com/projects/168dab35-a889-4b1c-99e7-6e0e44f611f6)
[![Quality Score](https://img.shields.io/scrutinizer/g/SebastianBerc/Repositories.svg?style=flat-square)](https://scrutinizer-ci.com/g/SebastianBerc/Repositories)
[![Total Downloads](https://img.shields.io/packagist/dt/sebastian-berc/repositories.svg?style=flat-square)](https://packagist.org/packages/sebastian-berc/repositories)

## Install

Via Composer

``` bash
$ composer require sebastian-berc/repositories
```

## Usage

Make your own repository with extends the abstract `\SebastianBerc\Repositories\Repository` class and implement `takeModel` method:

``` php
class MyRepository extends \SebastianBerc\Repositories\Repository
{
    /**
     * Return fully qualified model class name.
     *
     * @return string
     */
    public function takeModel()
    {
        return MyModel::class
    }
}
```

#### Usage with Dependency Incjection

Wherever Laravel provides dependency injection you can attach your repository like this:

``` php
class UsersController extends Controller 
{
    /**
     * Contains users repository.
   	 *
     * @var UsersRepository
     */
    protected $repository;

	/**
	 * Creates a new instance of users controller.
	 *
	 * @param UsersRepository $repository
	 */
    public function __construct(UsersRepository $repository)
    {
        $this->repository = $repository;
    }
}
```

#### Usage without Dependency Injection

If you need a repository without dependency injection you can use the static method like this:

``` php
	/**
	 * Creates a new users.
	 */
    public function store(Request $request)
    {
        $repository = UsersRepository::instance();
        
        return $repository->create($request->all());
    }
```

#### Methods

This gives you access to methods such as...

creating a new query to get all results from repository:

``` php
// Definition:
$repository->all(array $columns = ['*']);
// Example:
$users = $repository->all(['name', 'value']);
```

[Need fix] creating a new basic where query clause on model and returns results:

``` php
// Definition:
$repository->where($column, $operator = '=', $value = null, $boolean = 'and', array $columns = ['*']);
// Example:
$repository->where('id', '<>', \Auth::user()->getKey(), 'and', ['activated', 'banned']);
```

creating a new query with pagination:

``` php
// Definition:
$repository->paginate($perPage = 15, array $columns = ['*']);
// Example:
$repository->paginate(50, ['name', 'value']);
```

saving a new model and return the instance:

``` php
// Definition:
$repository->create(array $attributes = []);
// Example:
$repository->create(['activated' => true, 'banned' => false]);
```

saving or updates the model in the database.

``` php
// Definition:
$repository->update($identifier, array $attributes = []);
// Example:
$repository->update(1, ['activated' => true, 'banned' => false]);
```
Also you can pass a model:

``` php
// Definition:
$repository->update($dirtyModel);
// Example:
$model = $repository->find(1);
$model->activated = true;
$repository->update($model);
```

even with additional attributes:

``` php
// Definition:
$repository->update($dirtyModel, ['activated' => true]);
// Example:
$model = $repository->find(1);
$model->activated = true;
$repository->update($model, ['banned' => false]);
```

delete the model from the database:

``` php
// Definition:
$repository->delete($identifier);
// Example:
$repository->delete(1);
```

find a model (only first result) by its primary key:

``` php
// Definition:
$repository->find($identifier, array $columns = ['*']);
// Example:
$repository->find(1, ['name', 'value']);
```

find a model (only first result) by its specified column and value:

``` php
// Definition:
$repository->findBy($column, $value, array $columns = ['*']);
// Example:
$repository->findBy('activated', true, ['id']);
```

find a model (only first result) by its specified columns and values presented as array:

``` php
// Definition:
$repository->findWhere(array $wheres, array $columns = ['*']);
// Example:
$repository->findWhere(['activated' => true, 'banned' => false], ['id']);
```

return total count of whole collection based on current query:

``` php
// Definition and example:
$repository->count();
```

fetch collection ordered and filtrated by specified columns for specified page and this method will return instance of `LengthAwarePaginator`:

``` php
// Definition:
$repository->fetch($page = 1, $perPage = 15, array $columns = ['*'], array $filter = [], array $sort = []);
// Example:
$repository->fetch(1, 15, ['*'], ['activated' => true, 'banned' => 'false'], ['id' => 'ASC']);
```

fetch simple collection without `LengthAwarePaginator`, but ordered and filtrated by specified columns for specified page:

``` php
// Definition:
$repository->simpleFetch($page = 1, $perPage = 15, array $columns = ['*'], array $filter = [], array $sort = []);
// Example:
$repository->simpleFetch(1, 15, ['*'], ['activated' => true, 'banned' => 'false'], ['id' => 'ASC']);
```

#### Eager loads

If your model has a relationship and you want to load it, you can do this with query results by calling the `with` method, for example:

``` php 
// Definition:
$repository->with($relations);
// Examples:
$repository->with('roles')->all();
$repository->with('roles', 'permissions')->all();
$repository->with(['roles', 'permissions])->all();

```

#### Criteria

Sometimes you need to prepare repeatable query that displays, for example, only active and not banned users or the latest news from the week before - to do this you can use a criteria:

``` php
class ActivatedAndNotBanned extends \SebastianBerc\Repositories\Criteria
{
    public function execute(Builder $query)
    {
        return $query->where(['activated' => true])->andWhere(['banned' => false]);
    }
}
```

Now you can use your criteria with repository:

``` php
$repository->criteria(new ActivatedAndNotBanned())->all();
```

#### Transformers

The data from your repositories can be sent to different places, for example, it may be your site or the same data with the exclusion of several columns can be shared with Web API, you can achieve through class `Transfomer`:

``` php
class UsersForListing extends \SebastianBerc\Repositories\Transformer
{
    public function transform($item)
    {
    	$item->fullname = $item->firstName . ' ' . $item->lastName;
        $item->password = '*****';

        return $item;
    }
}
```

When you have your own `Transformer` you can apply it to repository:

``` php
$repository->setTransformer(UsersForListing::class)->all();

```


#### Cached repository results

To hold query results in a cache just add the implementation of the interface `ShouldCache` to your repository:

``` php
class MyRepository extends \SebastianBerc\Repositories\Repository implements \SebastianBerc\Repositories\Contracts\ShouldCache
{
    /**
     * Return fully qualified model class name.
     *
     * @return string
     */
    public function takeModel()
    {
        return MyModel::class;
    }
}
```

And its magic because from now everything will be cached :).


## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email contact@sebastian-berc.pl instead of using the issue tracker.

## Credits

- [Sebastian Berć](https://github.com/SebastianBerc)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
