# Discover

>Discover the true potential of your data

**Please note: Discover is lacking some testing, everything might not work... Pull requests are warmly welcomed!**

_Discover is a package aimed at the [Laravel Framework](https://laravel.com), although with some modification should be usable standalone._

The year is 2016, and _everyone_ wants searchable _everything_. People want to search all blog posts, all comments, all pages, _everything_, by a simple search term. Of course, you've never really dealt with this kind of search before; sure, you can do a full-text search in MySQL, but even though it's possible, it's not the best idea. Instead, you've probably come to the conclusion that you should use a document database, such as Elasticsearch. Having never used it before, you imagine it can't be much harder than any other database, and then you get stuck. For two ~days~ weeks.

Elasticsearch is a wonderful and incredibly powerful tool. However, it can also be very daunting to get started with at first, since using the official PHP library often results in doing about 7 nested arrays to do something remotely useful. Of course, that's pretty awful and doesn't really wanna make you use Elasticsearch, and that's why I built Discover.

Discover aims to make creating **searchable** models with relations through **subdocuments** (_don't worry if you have no idea what this means, you don't have to care_) a seamless experience that _just works_. When you call `save` on your model, Discover will make sure to index the data you want to Elasticsearch, and skip the rest.

## Prerequisites

To use this, you'll need a document database of some form. As of now, there is only support for the Elasticsearch driver, although PRs are accepted to implement more.

## Installation

Installation is most conveniently done through composer with a simple

```
composer require phroggyy/discover dev-master
```
_As of now, there is no stable release, as I wish to create some more tests and make sure everything works before tagging it._

Once that's done, you'll want to register the service provider in your `config/app.php` by adding this line:

```
Phroggyy\Discover\DiscoverServiceProvider::class,
```

That's all there's to it, you can now start using Discover throughout your application!

## Configuration

If you just want to use one Elasticsearch master node, and stick with a pretty standard configuration, you won't need to publish the configuration file. However, at the very least, you should set up the `ELASTICSEARCH_HOST` in your `.env` file if it is not on localhost.

The default is to connect to `localhost:9200`, the port can be changed through `ELASTICSEARCH_PORT`.

To publish the configuration file, simply run

```
php artisan vendor:publish --provider=Phroggyy\Discover\DiscoverServiceProvider
```

## Usage

In order to start using discover, there are two things you'll wanna pay attention to: migrations and models.

### Migrations

Discover ships with a handy trait to perform migrations on your Elasticsearch database. In your migration, simply use the trait `MigratesElasticSearchIndices` and you'll suddenly be able to run `$this->migrateIndex`. The way this works is in the following way

```
public function up ()
{
    $this->migrateIndex(new ModelToIndex, $version, $properties);
}
```

_Please note that you need to setup the model before running migrations with this method._

This will let you specify the properties on the index. You can also change the number of shards, replicas, or the type to index should you wish to do so.

Doing this, you can simply use `php artisan migrate` to migrate your database. Please note that, right now, there is no `down` method.

### Models

If you have an Elasticsearch database, you probably want some data in it. So, in order to store a simple model as a document, all you have to specify in your model is the following (here using the example _Post_ model):

```
class Post extends Model implements Searchable
{
    use Discoverable;
    
    protected $documentFields = [
        'title',
        'body',
    ];
}
```

This will ensure that when you save a post, a document is created (or updated) in Elasticsearch containg the data specified in `$documentFields`. If you wish to store all attributes in Elasticsearch (which is quite pointless), you can even leave out `$documentFields`.

#### Nested types (subdocuments)

Elasticsearch has something called nested types, which essentially allows your documents to... have other documents inside it. This is a great way to represent relationships in a document-oriented way. Imagine, for example, that this `Post` has some `Comment`s. Whereas in a relational database, you would let the comment have a `post_id`, here you can store all comments _inside_ the `Post` document. This makes querying _a lot faster_. 

_Elasticsearch also supports parent-child relationships which works similar to how you'd structure a relational database, but although indexing is faster, searching that kind of relationship can be 5-10x slower._

So, if you would like all `Comment`s to _automatically_ be indexed as subdocuments of your `Post`s, it's quite straightforward; you just need to set a `$documentType`!

```
class Comment extends Model implements Searchable
{
    use Discoverable;
    
    protected $documentType = Post::class.'/message';
    
    protected $documentFields = [
        'id',
        'message'
    ];
}
```

This will ensure that whenever a comment is saved with Eloquent, it's also indexed in Elasticsearch.

## General Note

There's still plenty to do in this package to make it feature complete and more user friendly, and PRs are more than welcome, this is just a start to do the bare minimum I required to build a product.

## To-do

  - [ ]  Automatically add the primary key to be indexed for subdocuments
  - [ ]  Make `down` migrations possible through moving the alias and then deleting the created index.
  - [ ] Make subdocument querying (searching) possible