# Getting started

GUMP is a standalone PHP data validation and filtering class that makes validating any data easy and painless without the reliance on a framework.

[![License](https://poser.pugx.org/wixel/gump/license)](https://packagist.org/packages/wixel/gump)
[![Total Downloads](https://poser.pugx.org/wixel/gump/downloads)](https://packagist.org/packages/wixel/gump)
[![Latest Stable Version](https://poser.pugx.org/wixel/gump/v/stable)](https://packagist.org/packages/wixel/gump)
[![Build Status](https://travis-ci.org/Wixel/GUMP.svg?branch=master)](https://travis-ci.org/Wixel/GUMP)
[![Coverage Status](https://coveralls.io/repos/github/Wixel/GUMP/badge.svg?branch=master)](https://coveralls.io/github/Wixel/GUMP?branch=master)

#### Install with composer

```
composer require wixel/gump
```

### Short format example for validations

```php
$is_valid = GUMP::is_valid(array_merge($_POST, $_FILES), [
    'username' => 'required|alpha_numeric',
    'password' => 'required|between_len,4;100',
    'avatar'   => 'required_file|extension,png;jpg'
]);

// recommended format (supported since v1.7) with field-rule specific error messages example
$is_valid = GUMP::is_valid(array_merge($_POST, $_FILES), [
    'username' => ['required', 'alpha_numeric'],
    'password' => ['required', 'between_len' => [6, 100]],
    'avatar'   => ['required_file', 'extension' => ['png', 'jpg']]
], [
    'username' => ['required' => 'Fill the Username field please.'],
    'password' => ['between_len' => '{field} must be between {param[0]} and {param[1]} characters.'],
    'avatar'   => ['extension' => 'Valid extensions for avatar are: {param}'] // "png, jpg"
]);

if ($is_valid === true) {
    // continue
} else {
    var_dump($is_valid); // array of error messages
}
```

### Short format example for filtering

```php
$filtered = GUMP::filter_input([
    'field'       => ' text ',
    'other_field' => 'Cool Title'
], [
    'field'       => ['trim', 'upper_case'],
    'other_field' => 'slug'
]);

var_dump($filtered['field']); // result: "TEXT"
var_dump($filtered['other_field']); // result: "cool-title"
```

### Long format example

```php
$gump = new GUMP();

$gump->validation_rules([
    'username'    => 'required|alpha_numeric|max_len,100|min_len,6',
    'password'    => 'required|max_len,100|min_len,6',
    'email'       => 'required|valid_email',
    'gender'      => 'required|exact_len,1|contains,m;f',
    'credit_card' => 'required|valid_cc'
]);

// field-specific error messages
$gump->set_fields_error_messages([
    'username'      => ['required' => 'Fill the Username field please, its required.'],
    'credit_card'   => ['extension' => 'Please enter a valid credit card.']
]);

$gump->filter_rules([
    'username' => 'trim|sanitize_string',
    'password' => 'trim',
    'email'    => 'trim|sanitize_email',
    'gender'   => 'trim',
    'bio'      => 'noise_words'
]);

$valid_data = $gump->run($_POST);

if ($valid_data === false) {
    var_dump($gump->get_readable_errors()); // For HTML: ['Field <span class="gump-field">Somefield</span> is required.'] 
    // or
    var_dump($gump->get_errors_array()); // For APIs?: ['field' => 'Field Somefield is required']
} else {
    var_dump($valid_data); // after filters result: ['field' => 'value']
}
```


:star: Available Validators
---------------------------
**Important:** If you use Pipe or Semicolon as parameter value, you **must** use array format.
```php
$is_valid = GUMP::is_valid(array_merge($_POST, $_FILES), [
    'field' => 'regex,/partOf;my|Regex/', // NO
    'field' => ['regex' => '/partOf;my|Regex/']) // YES
]);
```

<div id="available_validators">

| Rule                                                                           | Description                                                                                                                                                                                               |
|--------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **required**                                                                   | Ensures the specified key value exists and is not empty.                                                                                                                                                  |
| **contains**,one;two;use array format if one of the values contains semicolons | Verify that a value is contained within the pre-defined value set.                                                                                                                                        |
| **contains_list**,value1;value2                                                | Verify that a value is contained within the pre-defined value set. Error message will NOT show the list of possible values.                                                                               |
| **doesnt_contain_list**,value1;value2                                          | Verify that a value is contained within the pre-defined value set. Error message will NOT show the list of possible values.                                                                               |
| **boolean**,strict                                                             | Determine if the provided value is a valid boolean. Returns true for: yes/no, on/off, 1/0, true/false. In strict mode (optional) only true/false will be valid which you can combine with boolean filter. |
| **valid_email**                                                                | Determine if the provided email has valid format.                                                                                                                                                         |
| **max_len**,240                                                                | Determine if the provided value length is less or equal to a specific value.                                                                                                                              |
| **min_len**,4                                                                  | Determine if the provided value length is more or equal to a specific value.                                                                                                                              |
| **exact_len**,5                                                                | Determine if the provided value length matches a specific value.                                                                                                                                          |
| **between_len**,3;11                                                           | Determine if the provided value length is between min and max values.                                                                                                                                     |
| **alpha**                                                                      | Determine if the provided value contains only alpha characters.                                                                                                                                           |
| **alpha_numeric**                                                              | Determine if the provided value contains only alpha-numeric characters.                                                                                                                                   |
| **alpha_dash**                                                                 | Determine if the provided value contains only alpha characters with dashed and underscores.                                                                                                               |
| **alpha_numeric_dash**                                                         | Determine if the provided value contains only alpha numeric characters with dashed and underscores.                                                                                                       |
| **alpha_numeric_space**                                                        | Determine if the provided value contains only alpha numeric characters with spaces.                                                                                                                       |
| **alpha_space**                                                                | Determine if the provided value contains only alpha characters with spaces.                                                                                                                               |
| **numeric**                                                                    | Determine if the provided value is a valid number or numeric string.                                                                                                                                      |
| **integer**                                                                    | Determine if the provided value is a valid integer.                                                                                                                                                       |
| **float**                                                                      | Determine if the provided value is a valid float.                                                                                                                                                         |
| **valid_url**                                                                  | Determine if the provided value is a valid URL.                                                                                                                                                           |
| **url_exists**                                                                 | Determine if a URL exists & is accessible.                                                                                                                                                                |
| **valid_ip**                                                                   | Determine if the provided value is a valid IP address.                                                                                                                                                    |
| **valid_ipv4**                                                                 | Determine if the provided value is a valid IPv4 address.                                                                                                                                                  |
| **valid_ipv6**                                                                 | Determine if the provided value is a valid IPv6 address.                                                                                                                                                  |
| **valid_cc**                                                                   | Determine if the input is a valid credit card number.                                                                                                                                                     |
| **valid_name**                                                                 | Determine if the input is a valid human name.                                                                                                                                                             |
| **street_address**                                                             | Determine if the provided input is likely to be a street address using weak detection.                                                                                                                    |
| **iban**                                                                       | Determine if the provided value is a valid IBAN.                                                                                                                                                          |
| **date**,d/m/Y                                                                 | Determine if the provided input is a valid date (ISO 8601) or specify a custom format (optional).                                                                                                         |
| **min_age**,18                                                                 | Determine if the provided input meets age requirement (ISO 8601). Input should be a date (Y-m-d).                                                                                                         |
| **max_numeric**,50                                                             | Determine if the provided numeric value is lower or equal to a specific value.                                                                                                                            |
| **min_numeric**,1                                                              | Determine if the provided numeric value is higher or equal to a specific value.                                                                                                                           |
| **starts**,Z                                                                   | Determine if the provided value starts with param.                                                                                                                                                        |
| **required_file**                                                              | Determine if the file was successfully uploaded.                                                                                                                                                          |
| **extension**,png;jpg;gif                                                      | Check the uploaded file for extension. Doesn't check mime-type yet.                                                                                                                                       |
| **equalsfield**,other_field_name                                               | Determine if the provided field value equals current field value.                                                                                                                                         |
| **guidv4**                                                                     | Determine if the provided field value is a valid GUID (v4)                                                                                                                                                |
| **phone_number**                                                               | Determine if the provided value is a valid phone number.                                                                                                                                                  |
| **regex**,/test-[0-9]{3}/                                                      | Custom regex validator.                                                                                                                                                                                   |
| **valid_json_string**                                                          | Determine if the provided value is a valid JSON string.                                                                                                                                                   |
| **valid_array_size_greater**,1                                                 | Check if an input is an array and if the size is more or equal to a specific value.                                                                                                                       |
| **valid_array_size_lesser**,1                                                  | Check if an input is an array and if the size is less or equal to a specific value.                                                                                                                       |
| **valid_array_size_equal**,1                                                   | Check if an input is an array and if the size is equal to a specific value.                                                                                                                               |
| **valid_twitter**                                                              | Determine if the provided value is a valid Twitter account.                                                                                                                                               |
</div>

:star: Available Filters
------------------------
Filter rules can also be any PHP native function (e.g.: rim).

<div id="available_filters">

| Filter                 | Description                                                                                                                  |
|------------------------|------------------------------------------------------------------------------------------------------------------------------|
| **noise_words**        | Replace noise words in a string (http://tax.cchgroup.com/help/Avoiding_noise_words_in_your_search.htm).                      |
| **rmpunctuation**      | Remove all known punctuation from a string.                                                                                  |
| **urlencode**          | Sanitize the string by urlencoding characters.                                                                               |
| **htmlencode**         | Sanitize the string by converting HTML characters to their HTML entities.                                                    |
| **sanitize_email**     | Sanitize the string by removing illegal characters from emails.                                                              |
| **sanitize_numbers**   | Sanitize the string by removing illegal characters from numbers.                                                             |
| **sanitize_floats**    | Sanitize the string by removing illegal characters from float numbers.                                                       |
| **sanitize_string**    | Sanitize the string by removing any script tags.                                                                             |
| **boolean**            | Converts ['1', 1, 'true', true, 'yes', 'on'] to true, anything else is false ('on' is specially useful for form checkboxes). |
| **basic_tags**         | Filter out all HTML tags except the defined basic tags.                                                                      |
| **whole_number**       | Convert the provided numeric value to a whole number.                                                                        |
| **ms_word_characters** | Convert MS Word special characters to web safe characters. ([“, ”, ‘, ’, –, …] => [", ", ', ', -, ...])          |
| **lower_case**         | Converts to lowercase.                                                                                                       |
| **upper_case**         | Converts to uppercase.                                                                                                       |
| **slug**               | Converts value to url-web-slugs.                                                                                             |
| **trim**               | Remove spaces from the beginning and end of strings (PHP).                                                                   |
</div>

#### Available Methods

```php
// Shorthand validation
is_valid(array $data, array $rules)

// Get or set the validation rules
validation_rules(array $rules);

// Get or set the filtering rules
filter_rules(array $rules);

// Runs the filter and validation routines
run(array $data);

// Strips and encodes unwanted characters
xss_clean(array $data);

// Sanitizes data and converts strings to UTF-8 (if available),
// optionally according to the provided field whitelist
sanitize(array $input, $whitelist = NULL);

// Validates input data according to the provided ruleset (see example)
validate(array $input, array $ruleset);

// Filters input data according to the provided filterset (see example)
filter(array $input, array $filterset);

// Returns human readable error text in an array or string
get_readable_errors($convert_to_string = false);

// Fetch an array of validation errors indexed by the field names
get_errors_array();

// Override field names with readable ones for errors
set_field_name($field, $readable_name);
```


Match data-keys against rules-keys
-------------
We can check if there is a rule specified for every data-key, by adding an extra parameter to the run method.

```
$gump->run($_POST, true);
```

If it doesn't match the output will be:
```
There is no validation rule for <span class=\"$field_class\">$field</span>
```

Return Values
-------------
`run()` returns one of two types:

*ARRAY* containing the successfully validated and filtered data when the validation is successful

*BOOLEAN* False when the validation has failed

`validate()` returns one of two types:

*ARRAY* containing key names and validator names when data does not pass the validation.

You can use this array along with your language helpers to determine what error message to show.

*BOOLEAN* value of TRUE if the validation was successful.

`filter()` returns the exact array structure that was parsed as the `$input` parameter, the only difference would be the filtered data.

###  Creating your own validators and filters

Adding custom validators and filters is made easy by using callback functions.

```php
/**
 * You would call it like 'equals_string,someString'
 *
 * @param string  $field Name of the field
 * @param array   $input Access to the whole input data
 * @param string  $param Rule parameters (optional)
 *
 * @return bool   true or false whether the validation was successful or not
 */
GUMP::add_validator("equals_string", function($field, $input, $param = null) {
    return $input[$field] === $param;
}, 'Field {field} does not equal {param}.');


/**
 * @param string  $value Value
 * @param string  $param Filter parameters (optional)
 *
 * @return mixed  result of filtered value
 */
GUMP::add_filter("upper", function($value, $params = null) {
    return strtoupper($value);
});
```

Alternately, you can simply create your own class that extends the GUMP class.

```php
class MyClass extends GUMP
{
    /**
     * @param string  $value Value
     * @param string  $param Filter parameters (optional)
     *
     * @return mixed  result of filtered value
     */
    public function filter_myfilter($value, $param = null)
    {
        return strtoupper($value);
    }

    /**
     * @param string  $field Name of the field
     * @param array   $input Access to the whole input data
     * @param string  $param Rule parameters (optional)
     *
     * @return bool   true or false whether the validation was successful or not
     */
    public function validate_myvalidator($field, $input, $param = null)
    {
        return $input[$field] === 'good_value';
    }
}

$validator = new MyClass();
$validated = $validator->validate($_POST, $rules);
```

Remember to create a public methods with the correct parameter types and parameter counts.

* For filter methods, prepend the method name with "filter_".
* For validator methods, prepend the method name with "validate_".

### Set Custom Field Names

You can easily override your form field names for improved readability in errors using the `GUMP::set_field_name($field, $readable_name)` method as follows:

```php
$data = [
    'str' => null
];

$rules = [
    'str' => 'required'
];

GUMP::set_field_name("str", "Street");

$validated = GUMP::is_valid($data, $rules);

if ($validated === true) {
    echo "Valid Street Address\n";
} else {
    print_r($validated);
}
```


Global configuration
--------------------
This configuration values would allow you to turn: `required|contains,value1;value2` into `required|contains:value1,value2`.

```php
GUMP::$rules_delimiter = '|';

GUMP::$rules_parameters_delimiter = ',';

GUMP::$rules_parameters_arrays_delimiter = ';';
```
