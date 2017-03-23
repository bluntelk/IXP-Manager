<?php

/*
 * Copyright (C) 2009-2017 Internet Neutral Exchange Association Company Limited By Guarantee.
 * All Rights Reserved.
 *
 * This file is part of IXP Manager.
 *
 * IXP Manager is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation, version v2.0 of the License.
 *
 * IXP Manager is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License v2.0
 * along with IXP Manager.  If not, see:
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */


namespace IXP\Mail\PatchPanelPort;

//use Entities\PatchPanelPort as PatchPanelPortEntity;
//use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
//use Illuminate\Queue\SerializesModels;
//use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Mailable for patch panel emails
 *
 * @author     Barry O'Donovan <barry@islandbridgenetworks.ie>
 * @category   PatchPanel
 * @package    IXP\Mail\PatchPanelPort
 * @copyright  Copyright (C) 2009-2017 Internet Neutral Exchange Association Company Limited By Guarantee
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU GPL V2.0
 */
class Connect extends Mailable
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {

        return $this;
    }
}
