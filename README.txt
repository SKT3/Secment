Required:
- node v6 + npm
- gulp

References:
https://nodejs.org/en/
https://gulpjs.com/

* Open command window and navigate to project's folder root(where "gulp.js" and "package.json" files are located). There we can execute the following commands:
- npm install - This command download all gulp dependencies and store them in "node_modules" folder. We execute this command only once, when we start new the project.
- gulp watch - This command create background proccess that watch all source files for changes and build static pages and compile resources in "/build" folders. We execute this command only once before we start making changes in source files.
- gulp - Command that build static pages, compile and minify resources.

* Development files locations:
- html - "/templates/source"
- css - "/styles/source"
- js - "/scripts/source"

* Production files locations:
- static html - "/"
- compiled css - "/styles/build"
- compiled js - "/scripts/build"