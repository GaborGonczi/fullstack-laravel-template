# Prerequisites

Install Docker and WSL (only on Windows).

# How to Use

Create a new repository based on this template, then clone that.

In the project root directory, run `composer install`.

Then, run `php artisan fullstack:sail` to set up and start the developer container.
During this process, you'll choose the appropriate database. If you encounter an error, restart Docker and WSL.

To start the container, run `./bin/dev start` (it will log you in by default). Use this command for future starts.

To install predefined packages, run: `php artisan fullstack:packages`.

To stop the container, exit and run `./bin/dev stop`.

To restart the container, run `./bin/dev restart`.