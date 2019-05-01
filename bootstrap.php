<?php

/**
 * @file
 * Implements bootstrap functions.
 */

function is_url_exist($url){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if($code == 200){
        $status = true;
    } else {
        $status = false;
    }
    curl_close($ch);
    return $status;
}

// Include addon functions only if its an admin request.
if (COCKPIT_ADMIN && !COCKPIT_API_REQUEST) {
    // Extend addon functions.
    $this->module('contentvalidation')->extend([
        'validate' => function () {
            $errors = $this->validateCollections();
            return $errors;
        },

        'validateCollections' => function() {
            $errors = [];
            $collections = $this->app->module('collections')->collections();
            foreach ($collections as $name => $collection) {
                $errors = array_merge($errors, $this->validateCollection($name, $collection));
            }
            return $errors;
        },

        'validateCollection' => function($name, $collection) {
            $cid = $collection['_id'];
            $fields = $collection['fields'];
            $items = $this->app->storage->find("collections/{$cid}")->toArray();
            $errors = [];

            foreach($items as $item) {
                $errors = array_merge($errors, $this->validateFields($name, $fields, $item));
            }
            return $errors;
        },

        'validateFields' => function($name, $fields, $data) {
            $errors = [];
            $errorData = [
                'module' => 'Collection',
                'entity' => $name,
                '_id' => $data['_id'],
                'name' => ($data['name'] ?? $data['title'] ?? $data[$fields[0]['name']]),
            ];

            foreach($fields as $field) {
                $errors = array_merge($errors, $this->validateField($field, $data[$field['name']], $errorData));
            }
            return $errors;
        },

        'validateField' => function($field, $fieldData, $errorData, $inRepeater = false, $debug = false) {
            $errors = [];
            if (array_key_exists('required', $field) && $field['required'] && !$fieldData) {
                $errors []= array_merge([
                    'violationtype' => 'Error',
                    'violation' => 'Required field ' . $field['label'] . ' [' . ($field['name'] ?? 'no-name') . '] does not have a value'
                ], $errorData);
            }

            if ($inRepeater && !$fieldData) {
                $errors []= array_merge([
                    'violationtype' => 'Error',
                    'violation' => 'An entry in repeater Field ' . $field['label'] . ' [' . ($field['name'] ?? 'no-name') . '] does not have a value'
                ], $errorData);
            }
            if ($field['type'] === 'image') {
                $errors = array_merge($errors, $this->validateImageField($field, $fieldData, $errorData, $inRepeater, $debug));
            } else if ($field['type'] === 'collectionlink' && $fieldData) {
                $errors = array_merge($errors, $this->validateCollectionLinkField($field, $fieldData, $errorData));
            } else if ($field['type'] === 'repeater' && count($fieldData) > 0) {
                $errors = array_merge($errors, $this->validateRepeaterField($field, $fieldData, $errorData, $debug));
            } else if ($field['type'] === 'set' && $fieldData) {
                $errors = array_merge($errors, $this->validateSetField($field, $fieldData, $errorData));
            }
            return $errors;
        },

        'validateImageField' => function($field, $fieldData, $errorData, $inRepeater, $debug) {
            $errors = [];
            $path = $fieldData['path'];
            if (empty($path) && $field['required']) {
                $errors [] = array_merge([
                    'violationtype' => 'Error',
                    'violation' => 'Required field ' . $field['label'] . ' [' . $field['name'] . '] does not have a image path specified'
                ], $errorData);
            } else if (empty($path) && $inRepeater) {
                $errors []= array_merge([
                    'violationtype' => 'Error',
                    'violation' => 'Image field ' . $field['label'] . ' [' . $field['name'] . '] in repeater does not have an image path specified'
                ], $errorData);
            } else if (stripos($path, 'http') === 0) {
                if (!is_url_exist($path)) {
                    $errors []= array_merge([
                        'violationtype' => 'Error',
                        'violation' => 'Field ' . $field['label'] . ' [' . $field['name'] . '] contains invalid image url "' . $path . '" that cannot be resolved!'
                    ], $errorData);
                }
            } else if (!file_exists(COCKPIT_DIR . '/' .$path)) {
                $errors []= array_merge([
                    'violationtype' => 'Error',
                    'violation' => 'Field ' . $field['label'] . ' [' . $field['name'] . '] contains invalid image url "' . $path . '" that cannot be resolved!'
                ], $errorData);
            } else if (stripos($path, '.gif')) {
                $errors []= array_merge([
                    'violationtype' => 'Warning',
                    'violation' => 'Field ' . $field['label'] . ' [' . $field['name'] . '] contains a GIF image which cannot be processed by the Sharp Image processing library used in Gatsby'
                ], $errorData);
            }
            return $errors;
        },

        'validateCollectionLinkField' => function($field, $fieldData, $errorData) {
            $collections = $this->app->module('collections')->collections();
            $collectionFound = false;
            $itemFound = false;
            $errors = [];

            if (array_key_exists('_id', $fieldData)) {
                $entries = [$fieldData];
            } else {
                $entries = $fieldData;
            }
            foreach($entries as $entry) {
                foreach ($collections as $name => $collection) {
                    if ($name === $entry['link']) {
                        $collectionFound = true;
                        $items = $this->app->storage->find("collections/" . $collection['_id'])->toArray();
                        foreach ($items as $item) {
                            if ($item["_id"] === $entry["_id"]) {
                                $itemFound = true;
                            }
                        }
                    }
                }

                if (!$collectionFound) {
                    $errors [] = array_merge([
                        'violationtype' => 'Error',
                        'violation' => 'Field ' . $field['label'] . ' [' . $field['name'] . '] contains a link to a Collection that doesn\'t exist any more!'
                    ], $errorData);
                } else if (!$itemFound) {
                    $errors [] = array_merge([
                        'violationtype' => 'Error',
                        'violation' => 'Field ' . $field['label'] . ' [' . $field['name'] . '] contains a link to a collection item that doesn\'t exist any more!'
                    ], $errorData);
                }
            }
            return $errors;
        },

        'validateRepeaterField' => function($field, $fieldData, $errorData, $debug = false) {
            $errors = [];
            foreach ($fieldData as $repeaterEntry) {
                $errors = array_merge($errors, $this->validateField($field['options']['field'], $repeaterEntry['value'], $errorData, true, $debug));
            }
            return $errors;
        },

        'validateSetField' => function($field, $fieldData, $errorData) {
            $errors = [];
            foreach($field['options']['fields'] as $subField) {
                $errors = array_merge($errors, $this->validateField($subField, $fieldData[$subField['name']], $errorData, false, false));
            }
            return $errors;
        },

        'createDeploy' => function () {
            return null;
        },
    ]);

    // Include admin.
    include_once __DIR__ . '/admin.php';
}
