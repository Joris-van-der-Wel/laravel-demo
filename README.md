# ShareTool
This is a demonstrative project that I've set up to explore the Laravel framework.

The project "ShareTool" is a web application which lets users easily share images, videos and other files. A user may create a "share" and upload files into that share. The share can then be configured to allow other registered users access, alternatively a public link may be generated. Shares may also be configured to require a password.

## Implementation

* Sail is used to setup a local development environment using Docker.
* Breeze was used as a starter kit to implement login, registration, password reset, etc.
* Tailwind is used for styling.
* TypeScript is used for a small amount of frontend code:
  * Periodic cross fading of the background image on the welcome page.
  * Clipboard integration to automatically copy the public link for shares.
* The database uses MySQL. A normalized model is implemented using migration files. The model includes:
  * Shares, which have a name, description, owner.
  * Share access configuration, which defines which users can access the share, if the share is public (using a secure token) and if the share is password protected (hashed).
  * Files, which belong to a single share, contains metadata about the file, the uploader and optionally caches thumbnail data.
  * Share audit log so that the owner can inspect how the share is being accessed.
* Eloquent is used to interact with the database, the project implements model classes, relationship mappings, extended query builders and factories. Database transactions are used for multi-step updates.
* A database seeder which fills the database with example data.
* A plain blade template is used to implement the welcome page for unauthenticated visitors.
* Routing definitions for static views, controllers and volt page components.
* Composer is used to manage dependencies.
* A service container to simplify usage of the RandomLib library. This is used for secure generation of random tokens. A real-time facade is used to access this service container.
* HTTP Controllers to generate temporary downloads URLs. This is in preparation for using S3 instead of the local driver so that large files can be downloaded by the browser directly from S3.
* Authorization policies and gates which are used to check access to shares and file. This includes the checking of share tokens and passwords.
* LiveWire Volt components are used to view and manage shares and their files. This includes page components and child components.
* A queueable job is used to automatically generate thumbnails of uploaded images using the Intervention image library.
* LiveWire events are used to invalide cached database queries. For example the share list refreshes after creating a new share.
* Laravel Reverb events are used to refresh the file list when a different user uploads or deletes a file. The file list is also refreshed after a thumbnail has been generated.
* Sail configuration for docker has been extended so that the queue runner and Laravel Reverb automatically start.
* Feature tests for volt components and authorization policies. These tests are currently not exhaustive.


## Developer guide

Make sure docker is installed and clone this repository.

To start the docker containers, run:

```sh
./sail up
```

Open a second terminal and run the following command to continuously build the frontend assets:

```sh
./sail npm run dev
```

Open a third terminal to run one-off artisan commands. Setup the database structure:

```sh
./sail artisan migrate:fresh
```

To setup demo data, run:

```sh
./sail artisan db:seed
```

Navigate to localhost and you can now log in with one of the following users:

| User            | Password |
|-----------------|----------|
| user1@localhost | password |
| user2@localhost | password |
| user3@localhost | password |
