> This package is currently in development

## Why?
Cached JSON Store.

You wanted to store some site preferences without the fiddle-faff and overkill of creating a Model and storing them in the database.

You don't want the preferences to be transferred from one site to another when you do a database dump. It's not everyone's use-case but sometimes you might prefer it.

You want to leverage the hierarchical nature of JSON to group values stored.

My use case was theme and sitewide settings.

It's not a _preference_ because you don't want to associate with a particular model. Creating an 'App' or 'Site' model just to store settings seems a bit naff.

## Testing
During testing data will not be persisted to disk.