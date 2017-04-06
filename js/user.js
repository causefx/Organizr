/**
 *	Part of a framework for a simple user authentication.
 */

User = {
	/**
	 * generates a random hex string
	 */
	randomString: function(len)
	{
		var hex = ['0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f'];
		var string = "";
		while(len-->0) { string += hex[parseInt(16*Math.random())]; }
		return string; },

	/**
	 * marks an input field as invalid/problematic
	 */
	markInvalid: function(input, reason) {
		var classes = "";
		if(input["class"]) { classes = input.getAttribute("class"); }
		input.setAttribute("class", classes + "form-control material errorz");
		input.title = reason;
		return false; },

	/**
	 * marks an input field as having passed validation
	 */
	markValid: function(input) {
		if(input.getAttribute("class")) {
			var stripped = input.getAttribute("class").replace("errorz", "");
			input.setAttribute("class", stripped); }
		input.title = "";
		return true; },

	/**
	 * user name validator
	 */
	validName: function(input)
	{
		var username = input.value;
		if(username.trim()=="") { return this.markInvalid(input, "You forgot your user name."); }
		if(username.indexOf("'")>-1) { return this.markInvalid(input, "Apostrophes are not allowed in user names."); }
		if(username.length<3) { return this.markInvalid(input, "Sorry, user names must be more than 2 letters."); }
		return this.markValid(input);
	},

	/**
	 * email address validator -- this uses the simplified email validation
	 * RegExp found on http://www.regular-expressions.info
	 */
	validEmail: function(input)
	{
		var valid = /[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/.test(input.value);
		if(!valid) { return this.markInvalid(input,"This is not a real email address..."); }
		return this.markValid(input);
	},

	/**
	 * checks whether the twice typed password is the same
	 */
	passwordMatch: function(input1, input2)
	{
		var matched = (input1.value==input2.value);
		if(!matched) { return this.markInvalid(input2, "The two passwords don't match"); }
		return this.markValid(input2);
	},

	/**
	 * Checks whether there is a password set
	 */
	validPassword: function(input)
	{
		var password = input.value;
		if(password.trim()=="") { return this.markInvalid(input, "You need to fill in a password"); }
		return this.markValid(input);
	},

	/**
	 * Checks whether the password is strong enough.
	 */
	strongPassword: function(input)
	{
		var password = input.value;
		if(!this.validPassword(input)) { return false; }
		// you want to mofidy the following line to suit your personal preference in
		// secure passwords. And remember that any policy you set has to work in an
		// international setting. Passwords can contain any Unicode character, and
		// are case sensitive. Don't rely on space-separated words, because several
		// big languages don't use spaces. Don't demand "numbers and letters" because
		// that just confuses your users. If you want to enforce strong passwords,
		// calculate how easy it is to guess the password, and report how quickly
		// you can figure out their password so that they pick a better one.
		if(password.length<4) { return this.markInvalid(input, "Your password is too easy to guess, please pick something longer. Use an entire sentence. if you like."); }
		return this.markValid(input);
	},

	/**
	 * Validate all values used for user registration, before submitting the form.
	 *
	 * NOTE: while this function does front-end validation, it is possible to bypass
	 * this function using a javascript console. So, in addition to this client-side
	 * validation the server will also be performing validation once it receives the data
	 */
	processRegistration: function()
	{
		var valid = true;
		var form = document.getElementById('registration');

		valid &= this.validName(form["username"]);
		valid &= this.validEmail(form["email"]);
		valid &= this.passwordMatch(form["password1"], form["password2"]);
		valid &= this.strongPassword(form["password1"]);

		if(valid) {
			form["sha1"].value = Sha1.hash(form["password1"].value);
			form["password1"].value = this.randomString(16);
			form["password2"].value = form["password1"].value;
			form.submit(); }
	},

	/**
	 * Validate all values used for user log in, before submitting the form.
	 *
	 * NOTE: while this function does front-end validation, it is possible to bypass
	 * this function using a javascript console. So, in addition to this client-side
	 * validation the server will also be performing validation once it receives the data
	 */
	processLogin: function()
	{
		var valid = true;
		var form = document.getElementById('login');

		valid &= this.validName(form["username"]);
		valid &= this.validPassword(form["password1"]);
		
		if(valid) {
			form["password"].value = form["password1"].value;
			form["sha1"].value = Sha1.hash(form["password1"].value);
			form["password1"].value = this.randomString(16);
			form.submit(); }
	},

	/**
	 * Validate all values used for email/password updating, before submitting the form.
	 *
	 * NOTE: while this function does front-end validation, it is possible to bypass
	 * this function using a javascript console. So, in addition to this client-side
	 * validation the server will also be performing validation once it receives the data
	 */
	processUpdate: function()
	{
		var valid = true;
		var update = false;
		var form = document.getElementById('update');

		// email?
		if(form["email"].value.trim()!="") {
			valid &= this.validEmail(form["email"]);
			if(valid) update = true; }

		// password?
		if(form["password1"].value.trim()!="") {
			valid &= this.passwordMatch(form["password1"], form["password2"]);
			valid &= this.strongPassword(form["password1"]);
			if(valid) {
				form["sha1"].value = Sha1.hash(form["password1"].value);
				form["password1"].value = this.randomString(16);
				form["password2"].value = form["password1"].value;
				update = true; }}

		if(valid && update) { form.submit(); }
	},

// ------------------------------------------------------------

  /**
   * A static shorthand function for appendChild
   */
  add: function(p, c) { p.appendChild(c); },

  /**
   * A more useful function for creating HTML elements.
   */
  make: function(tag, properties) {
      var tag = document.createElement(tag);
      if(properties !== null) {
        for(property in properties) {
          tag[property] = properties[property]; }}
      return tag; },

  /**
   * Inject a generic login form into the element passed as "parent"
   */
  injectLogin: function(parent) {
    // eliminate the need to type "this." everywhere in the function
    var add = this.add;
    var make = this.make;

    var form = this.make("form", {id: "usered_login_form", action: ".", method: "POST"});
    add(form, make("label", {"for": "usered_username", innerHTML: "user name"}));
    add(form, make("input", {id: "usered_username", type: "text"}));
    add(form, make("label", {"for": "usered_password", innerHTML: "password"}));
    add(form, make("input", {id: "usered_password", type: "password"}));
    add(form, make("input", {id: "usered_login_button", type: "submit", value: "log in"}));
    add(parent, form);
  },

  /**
   * Inject a generic logout form into the element passed as "parent"
   */
  injectLogout: function(parent) {
    // eliminate the need to type "this." everywhere in the function
    var add = this.add;
    var make = this.make;

    var form = make("form", {id: "usered_logout_form", action: ".", method: "POST"});
    add(form, make("input", {type: "hidden", name: "op", value: "logout"}));
    add(form, make("input", {id: "usered_logout_button", type: "submit", value: "log out"}));
    add(parent, form)
  }
};
