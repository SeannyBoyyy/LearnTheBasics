do i need composer for that? what if i only want to use .env

GitHub Copilot
You only need Composer if you want to use a package like vlucas/phpdotenv to load .env files automatically.

If you do not want to use Composer or any external package, you can load the .env file manually with a few lines of PHP. Here’s a simple way:

How to use:

Place your .env file in the project root.
Use getenv('YOUR_ENV_KEY') in your PHP code to access the values.
No Composer or external libraries required!