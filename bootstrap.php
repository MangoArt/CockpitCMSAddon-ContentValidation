<?php

/**
 * @file
 * Implements bootstrap functions.
 */

function validateFields($app, $name, $fields, $data) {
    $errors = [];
    $errorData = [
        'module' => 'Collection',
        'entity' => $name,
        '_id' => $data['_id'],
        'name' => ($data['name'] ?? $data['title'] ?? $data[$fields[0]['name']]),
    ];

    foreach($fields as $field) {
        $errors = array_merge($errors, validateField($app, $field, $data[$field['name']], $errorData));
    }

    return $errors;
}

function validateField($app, $field, $fieldData, $errorData) {
    $errors = [];
    if (array_key_exists('required', $field) && $field['required'] && !$fieldData) {
        $errors []= array_merge([
            'violationtype' => 'Error',
            'violation' => 'Required field ' . $field['label'] . ' [' . $field['name'] . '] does not have a value'
        ], $errorData);
    }

    if ($field['type'] === 'image' && $fieldData) {
        $path = $fieldData['path'];
        if (empty($path) && $field['required']) {
            $errors []= array_merge([
                'violationtype' => 'Error',
                'violation' => 'Required field ' . $field['label'] . ' [' . $field['name'] . '] does not have a image path specified'
            ], $errorData);
        } else if (stripos($path, 'http') === 0) {
            if (!is_url_exist($path)) {
                $errors []= array_merge([
                    'violationtype' => 'Error',
                    'violation' => 'Field ' . $field['label'] . ' [' . $field['name'] . '] contains invalid image url "' . $path . '" that cannot be resolved!'
                ], $errorData);
            }
        } else if (!file_exists($path)) {
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
    }


    if ($field['type'] === 'collectionlink' && $fieldData) {
        $collections = $app->module('collections')->collections();
        $collectionFound = false;
        $itemFound = false;
        foreach ($collections as $name => $collection) {
            if ($name === $fieldData['link']) {
                $collectionFound = true;
                $items = $app->storage->find("collections/" . $collection['_id'])->toArray();
                foreach ($items as $item) {
                    if ($item["_id"] === $fieldData["_id"]) {
                        $itemFound = true;
                    }
                }
            }
        }

        if (!$collectionFound) {
            $errors []= array_merge([
                'violationtype' => 'Error',
                'violation' => 'Field ' . $field['label'] . ' [' . $field['name'] . '] contains a link to a Collection that doesn\'t exist any more!'
            ], $errorData);
        } else if (!$itemFound) {
            $errors []= array_merge([
                'violationtype' => 'Error',
                'violation' => 'Field ' . $field['label'] . ' [' . $field['name'] . '] contains a link to a collection item that doesn\'t exist any more!'
            ], $errorData);
        }
    }

    return $errors;
}


function is_url_exist($url){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if($code == 200){
        $status = true;
    }else{
        $status = false;
    }
    curl_close($ch);
    return $status;
}

// Include addon functions only if its an admin request.
if (COCKPIT_ADMIN && !COCKPIT_API_REQUEST) {
  // Extend addon functions.
  $this->module('errorcheck')->extend([
    'validate' => function () {
        // Save collections.

      $result = [];
      $collections = $this->app->module('collections')->collections();
      foreach ($collections as $name => $collection) {
        $cid = $collection['_id'];
        $fields = $collection['fields'];
        $items = $this->app->storage->find("collections/{$cid}")->toArray();

        if (count($items)) {
          foreach($items as $item) {
            $result = array_merge($result, validateFields($this->app, $name, $fields, $item));
          }
        }
      }

        return $result;
    },

    'createDeploy' => function () {
      return null;
    },
  ]);

  // Include admin.
  include_once __DIR__ . '/admin.php';
}
