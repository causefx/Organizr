# Changelog

## 1.1.0
- Added event `onInit` which is fired when the component is completely initialized. Closes issue [#80](https://github.com/rstaib/jquery-steps/issues/80)
- Added event `onContentLoaded` which is fired when the step content is loaded (only in async cases relevant) Closes issue [#88](https://github.com/rstaib/jquery-steps/issues/88) and [#97](https://github.com/rstaib/jquery-steps/issues/97)

## 1.0.8
- Fixed issue [#91](https://github.com/rstaib/jquery-steps/issues/91) (`stepChanged` event is fired before transitions are done)

## 1.0.7
- Small fix. Closes issue [#58](https://github.com/rstaib/jquery-steps/issues/58)
- Set the default value of `enableCancelButton` for backward compatibility reasons to `false`

## 1.0.6
- Small fix. Closes issue [#56](https://github.com/rstaib/jquery-steps/issues/56)

## 1.0.5

- Added a cancel button
- Fixed a bug regarding adding steps dynamically. Closes issue [#56](https://github.com/rstaib/jquery-steps/issues/56)

## 1.0.4

- Fixed an issue regarding currentIndex on finish and finished event. Closes issue [#24](https://github.com/rstaib/jquery-steps/issues/24) and [#33](https://github.com/rstaib/jquery-steps/issues/33)

## 1.0.3

- Adding an id to the outer control wrapper tag will have as of now an impact on the internal uniqueid handling and therefore to the sub tag ids as well

## 1.0.2

- Add destroy method to remove the control functionality completely

## 1.0.1

- Fixed an iframe border and scrolling issue for older browsers (IE8 and lower)

## 1.0.0

- Nested tags which have the same node name as the body tag cause an exception. Closes issue [#4](https://github.com/rstaib/jquery-steps/issues/4)
- Separated data and UI changes from each other and improved code for testability
- Optimized code for better minification
- Configurable clearfix css class
- Vertical step navigation (default: horizontal)
- Removed `"use strict";` because of an ASP.Net tracing issue related to FF (see jQuery ticket: #13335)

## 0.9.7

- On finish failed the last step button does not become highlighted as error. Closes issue [#3](https://github.com/rstaib/jquery-steps/issues/3)
- Advanced accessibility support (WAI-ARIA)
- Replace Number() by parseInt() for parsing `string` to `int` values
- Add `"use strict";` and some other recommended things like the leading `;`
- Substitute `ol` by `ul` tag for step navigation
- Improve performance due to code refactoring

## 0.9.6

- Make css class for the outer component wrapper editable
- Add saveState option flag to enable/disable state persistence (saves last active step position)
- Add current class to step title and body for convinient css targeting [#2](https://github.com/rstaib/jquery-steps/issues/2)
- Add a bugfix related to the `startIndex` property
- Add a bugfix related to focusing after step changes
