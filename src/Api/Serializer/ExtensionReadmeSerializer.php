<?php

namespace Cmf\Api\Serializer;

class ExtensionReadmeSerializer extends AbstractSerializer
{
    /**
     * @param \Cmf\Extension\Extension $extension
     * @return array
     */
    protected function getDefaultAttributes($extension)
    {
        $attributes = [
            'content' => $extension->getReadme()
        ];

        return $attributes;
    }

    public function getId($extension)
    {
        return $extension->getId();
    }

    public function getType($extension)
    {
        return 'extension-readmes';
    }
}
