<?php
// FILE: /app/helpers/Validation.php

/**
 * Validation helper class
 *
 * Provides validation methods for user input.
 */
class Validation
{
    private $errors = [];
    private $data = [];

    /**
     * Constructor
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->data = $data;
    }

    /**
     * Validate required field
     *
     * @param string $field
     * @param string $message
     * @return self
     */
    public function required($field, $message = null)
    {
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = $message ?? "The {$field} field is required.";
        }

        return $this;
    }

    /**
     * Validate email
     *
     * @param string $field
     * @param string $message
     * @return self
     */
    public function email($field, $message = null)
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?? "The {$field} must be a valid email address.";
        }

        return $this;
    }

    /**
     * Validate minimum length
     *
     * @param string $field
     * @param int $min
     * @param string $message
     * @return self
     */
    public function min($field, $min, $message = null)
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field] = $message ?? "The {$field} must be at least {$min} characters.";
        }

        return $this;
    }

    /**
     * Validate maximum length
     *
     * @param string $field
     * @param int $max
     * @param string $message
     * @return self
     */
    public function max($field, $max, $message = null)
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field] = $message ?? "The {$field} must not exceed {$max} characters.";
        }

        return $this;
    }

    /**
     * Validate numeric value
     *
     * @param string $field
     * @param string $message
     * @return self
     */
    public function numeric($field, $message = null)
    {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?? "The {$field} must be a number.";
        }

        return $this;
    }

    /**
     * Validate value matches another field
     *
     * @param string $field
     * @param string $matchField
     * @param string $message
     * @return self
     */
    public function matches($field, $matchField, $message = null)
    {
        if (isset($this->data[$field]) && isset($this->data[$matchField]) &&
            $this->data[$field] !== $this->data[$matchField]) {
            $this->errors[$field] = $message ?? "The {$field} must match {$matchField}.";
        }

        return $this;
    }

    /**
     * Validate unique value in database
     *
     * @param string $field
     * @param string $table
     * @param string $column
     * @param int|null $excludeId
     * @param string $message
     * @return self
     */
    public function unique($field, $table, $column = null, $excludeId = null, $message = null)
    {
        if (!isset($this->data[$field])) {
            return $this;
        }

        $column = $column ?? $field;
        $db = Database::getInstance();

        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = :value";

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
        }

        $db->query($sql);
        $db->bind(':value', $this->data[$field]);

        if ($excludeId !== null) {
            $db->bind(':exclude_id', $excludeId);
        }

        $result = $db->fetch();

        if ($result['count'] > 0) {
            $this->errors[$field] = $message ?? "The {$field} is already taken.";
        }

        return $this;
    }

    /**
     * Validate value is in array
     *
     * @param string $field
     * @param array $values
     * @param string $message
     * @return self
     */
    public function in($field, $values, $message = null)
    {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $this->errors[$field] = $message ?? "The {$field} is invalid.";
        }

        return $this;
    }

    /**
     * Validate URL
     *
     * @param string $field
     * @param string $message
     * @return self
     */
    public function url($field, $message = null)
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
            $this->errors[$field] = $message ?? "The {$field} must be a valid URL.";
        }

        return $this;
    }

    /**
     * Validate date format
     *
     * @param string $field
     * @param string $format
     * @param string $message
     * @return self
     */
    public function date($field, $format = 'Y-m-d', $message = null)
    {
        if (isset($this->data[$field])) {
            $d = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$d || $d->format($format) !== $this->data[$field]) {
                $this->errors[$field] = $message ?? "The {$field} is not a valid date.";
            }
        }

        return $this;
    }

    /**
     * Check if validation passed
     *
     * @return bool
     */
    public function passes()
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     *
     * @return bool
     */
    public function fails()
    {
        return !$this->passes();
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Get first error message
     *
     * @return string|null
     */
    public function firstError()
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    /**
     * Static validation method
     *
     * @param array $data
     * @param array $rules
     * @return Validation
     */
    public static function make($data, $rules)
    {
        $validator = new self($data);

        foreach ($rules as $field => $ruleSet) {
            $ruleSet = explode('|', $ruleSet);

            foreach ($ruleSet as $rule) {
                if (strpos($rule, ':') !== false) {
                    list($ruleName, $ruleValue) = explode(':', $rule, 2);
                    $params = explode(',', $ruleValue);
                    array_unshift($params, $field);
                    call_user_func_array([$validator, $ruleName], $params);
                } else {
                    $validator->$rule($field);
                }
            }
        }

        return $validator;
    }
}
