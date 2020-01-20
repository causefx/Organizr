# Contributing to this project

Please take a moment to review this document in order to make the contribution
process easy and effective for everyone involved.

Following these guidelines helps to communicate that you respect the time of
the developers managing and developing this open source project. In return,
they should reciprocate that respect in addressing your issue or assessing
patches and features.


## Using the issue tracker

The issue tracker is the preferred channel for [bug reports](#bug-reports),
[features requests](#feature-requests) and submitting pull requests, but please
respect the following restrictions:

* Please **do not** use the issue tracker for personal support requests (use
  [Stack Overflow](http://stackoverflow.com) or IRC).

* Please **do not** derail or troll issues. Keep the discussion on topic and
  respect the opinions of others.


## Bug reports

A bug is a _demonstrable problem_ that is caused by the code in the repository.
Good bug reports are extremely helpful - thank you!

Guidelines for bug reports:

1. **Use the GitHub issue search.** Check if the issue has already been
   reported.

2. **Check if the issue has been fixed.** Try to reproduce it using the
   latest `master` or development branch in the repository.

3. **Provide environment details.** Provide your operating system, browser(s),
   devices, and breakpoints.js version.

4. **Create an isolated and reproducible test case.** Create a [reduced test
   case](http://css-tricks.com/6263-reduced-test-cases/).

5. **Include a live example.** Make use of jsFiddle or jsBin to share your
   isolated test cases.

A good bug report shouldn't leave others needing to chase you up for more
information. Please try to be as detailed as possible in your report. What is
your environment? What steps will reproduce the issue? What browser(s) and OS
experience the problem? What would you expect to be the outcome? All these
details will help people to fix any potential bugs.

Example:

> Short and descriptive example bug report title
>
> A summary of the issue and the browser/OS environment in which it occurs. If
> suitable, include the steps required to reproduce the bug.
>
> 1. This is the first step
> 2. This is the second step
> 3. Further steps, etc.
>
> `<url>` - a link to the reduced test case
>
> Any other information you want to share that is relevant to the issue being
> reported. This might include the lines of code that you have identified as
> causing the bug, and potential solutions (and your opinions on their
> merits).


## Feature requests

Feature requests are welcome. But take a moment to find out whether your idea
fits with the scope and aims of the project. It's up to *you* to make a strong
case to convince the project's developers of the merits of this feature. Please
provide as much detail and context as possible.

## Pull Request Guidelines

You must understand that by contributing code to this project, you are granting
the authors (and/or leaders) of the project a non-exclusive license to
re-distribute your code under the current license and possibly re-license the
code as deemed necessary.

* To instantiate a context or use it, use the variable **that** instead of
  **_this**.
* Please check to make sure that there aren't existing pull requests attempting
  to address the issue mentioned. We also recommend checking for issues related
  to the issue on the tracker, as a team member may be working on the issue in
  a branch or fork.
* Non-trivial changes should be discussed in an issue first
* If your change affects the distributed files, re-generate them using the
  [grunt procedure](#using-grunt)
* If possible, add relevant tests to cover the change
* Write a convincing description of your PR and why we should land it

## Using Grunt

We are using node and grunt to build and (in the future) test this project.
This means that you must setup a local development environment:

1. Install `node` and `npm` using your preferred method
2. Install the grunt CLI: `npm install -g grunt-cli`
3. Install the project's development dependencies: `npm install`
4. Run the various grunt tasks as needed:
   - `grunt`: clean the distribution files and re-build them
   - `grunt dist`: build the distribution files
   - `grunt clean`: clean the distribution files
   - `grunt dist`: build the javascript distribution files
   - `grunt watch`: watch for changes in the source files and build the
     distribution files as needed
