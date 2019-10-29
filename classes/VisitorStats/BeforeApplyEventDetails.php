<?php

/*
 * Visitor stats addon for Bear CMS
 * https://github.com/bearcms/visitor-stats-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

namespace BearCMS\VisitorStats;

/**
 * @property \BearFramework\App\Response $response
 * @property bool $preventDefault
 */
class BeforeApplyEventDetails
{

    use \IvoPetkov\DataObjectTrait;

    /**
     * 
     * @param \BearFramework\App\Response $response
     */
    public function __construct(\BearFramework\App\Response $response)
    {
        $this
            ->defineProperty('response', [
                'type' => \BearFramework\App\Response::class
            ])
            ->defineProperty('preventDefault', [
                'type' => 'bool',
                'init' => function () {
                    return false;
                }
            ]);
        $this->response = $response;
    }
}
