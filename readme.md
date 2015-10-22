Example of exploiting a application with a readable `/.git/*`

#How to use
- Clone the repo

`git clone https://github.com/RonnieSkansing/gitrip gitrip`

- In the example folder, move the `..git` to `.git`

`mv example/..git example/.git`

- Start a development server in the root of the example folder

`php -S localhost:8080 -t ./example`

- Run the script with the url to the the public readable .git/index

` php gitrip.php http://localhost:8080/.git/index build`

- The script has now retrieved the git index, used the data to collect and download all the git object files. The object files have then been converted to their original files. The foreign repo is now cloned locally and the private data is leaked.
