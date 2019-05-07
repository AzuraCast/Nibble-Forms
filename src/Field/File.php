<?php
namespace AzuraForms\Field;

final class File extends AbstractField
{
    protected $mime_types = [
        'image' => [
            'image/gif', 'image/gi_', 'image/png', 'application/png', 'application/x-png',
            'image/jp_', 'application/jpg', 'application/x-jpg', 'image/pjpeg', 'image/jpeg'
        ],
        'document' => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/mspowerpoint', 'application/powerpoint', 'application/vnd.ms-powerpoint',
            'application/x-mspowerpoint', 'application/plain', 'text/plain', 'application/pdf',
            'application/x-pdf', 'application/acrobat', 'text/pdf', 'text/x-pdf', 'application/msword',
            'pplication/vnd.ms-excel', 'application/msexcel', 'application/doc',
            'application/vnd.oasis.opendocument.text', 'application/x-vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet', 'application/x-vnd.oasis.opendocument.spreadsheet',
            'application/vnd.oasis.opendocument.presentation', 'application/x-vnd.oasis.opendocument.presentation'
        ],
        'archive' => [
            'application/x-compressed', 'application/gzip-compressed', 'gzip/document',
            'application/x-zip-compressed', 'application/zip', 'multipart/x-zip',
            'application/tar', 'application/x-tar', 'applicaton/x-gtar', 'multipart/x-tar',
            'application/gzip', 'application/x-gzip', 'application/x-gunzip', 'application/gzipped'
        ]
    ];

    protected $error_types = [
        'image' => 'File must be an image, e.g example.jpg or example.gif',
        'archive' => 'File must be an archive, e.g example.zip or example.tar',
        'document' => 'File must be a document, e.g example.doc or example.pdf',
        'all' => 'File must be a document, archive or image.',
        'custom' => 'File is invalid.'
    ];

    public function configure(array $config = []): void
    {
        parent::configure($config);

        $file_options = [
            'max_size' => 10 * 1024 * 1024,
            'width' => 1600,
            'height' => 1600,
            'min_width' => 0,
            'min_height' => 0,
            'type' => 'all',
        ];

        foreach($file_options as $option_key => $option_default) {
            $this->options[$option_key] = $this->attributes[$option_key] ?? $option_default;
            unset($this->attributes[$option_key]);
        }

        if (is_array($this->options['type'])) {
            $this->options['mime_types'] = $this->options['type'];
            $this->options['type'] = 'custom';
        } else if (isset($this->mime_types[$this->options['type']])) {
            $this->options['mime_types'] = $this->mime_types[$this->options['type']];
        }

        $this->options['button_text'] = $this->attributes['button_text'] ?? 'Select File';
        $this->options['button_icon'] = $this->attributes['button_icon'] ?? null;
    }

    public function getField($form_name): ?string
    {
        list($attribute_string, $class) = $this->_attributeString();

        $button_text = $this->options['button_text'];

        if ($this->options['button_icon'] !== null) {
            $button_text .= sprintf(' <i class="material-icons" aria-hidden="true">%1$s</i>', $this->options['button_icon']);
        }

        $output = '<button name="%1$s_button" id="%2$s_%1$s_button" class="file-upload btn btn-primary btn-block text-center %3$s" type="button">';
        $output .= '%4$s';
        $output .= '</button>';
        $output .= '<small class="file-name"></small>';
        $output .= '<input type="file" name="%1$s" id="%2$s_%1$s" style="visibility: hidden; position: absolute;">';

        return sprintf($output,
            $this->getFullName(),
            $form_name,
            $class,
            $button_text
        );
    }

    protected function _validateValue($val): bool
    {
        if ($this->options['required']) {
            if ($val['error'] != 0 || $val['size'] == 0) {
                $this->errors[] = 'This field is required.';
                return false;
            }
        }

        if ($val['error'] == \UPLOAD_ERR_OK) {
            if ($val['size'] > $this->options['max_size']) {
                $this->errors[] = sprintf('File must be less than %sMB.',
                    round($this->options['max_size'] / 1024 / 1024, 2)
                );
                return false;
            }

            if ($this->options['type'] == 'image') {
                $image = getimagesize($val['tmp_name']);
                if ($image[0] > $this->options['width'] || $image[1] > $this->options['height']) {
                    $this->errors[] = sprintf('File must contain an image no more than %s pixels wide and %s pixels tall.',
                        $this->options['width'],
                        $this->options['height']
                    );
                    return false;
                }
                if ($image[0] < $this->options['min_width'] || $image[1] < $this->options['min_height']) {
                    $this->errors[] = sprintf('File must contain an image at least %s pixels wide and %s pixels tall.',
                        $this->options['min_width'],
                        $this->options['min_height']
                    );
                    return false;
                }

                if (!in_array($image['mime'], $this->options['mime_types'])) {
                    $this->errors[] = $this->error_types[$this->options['type']];
                    return false;
                }
            } elseif (!in_array($val['type'], $this->options['mime_types'])) {
                $this->errors[] = $this->error_types[$this->options['type']];
                return false;
            }
        }

        return true;
    }

    protected function _attributeString()
    {
        $class = '';

        if (!empty($this->error)) {
            $class = 'error';
        }

        $attribute_string = '';
        foreach ($this->attributes as $attribute => $val) {
            if ($attribute == 'class') {
                $class .= ' ' . $val;
            } else if ($val !== false) {
                $attribute_string .= ' '.($val === true ? $attribute : "$attribute=\"$val\"");
            }
        }

        return [$attribute_string, $class];
    }
}
