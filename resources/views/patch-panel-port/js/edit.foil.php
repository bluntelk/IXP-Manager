<script>
    function setToday(inputName){
        $( "#"+inputName ).val( $( "#date" ).val() );
    }

    var notesIntro = "### <?= date( "Y-m-d" ) . ' - ' .$t->user->getUsername() ?> \n\n\n\n";

    /**
     * hide the help block at loading
     */
    $('.help-block').hide();

    $( document ).ready(function() {

        /**
         * display the duplex ports area if a duplex port has been setted to the patch panel port
         */
        if( <?= (int)$t->hasDuplex ?> ){
            $( '#duplex' ).click();
        }

        /**
         * display or hide the duplex port area
         */
        $( '#duplex' ).change( function(){
            if( this.checked ){
                $( "#duplex-port-area" ).show();
            } else {
                $( "#duplex-port-area" ).hide();
            }
        });


        /**
         * read only inputs
         */
        $( "#number" ).prop( 'readonly' , true);
        $( "#patch_panel" ).prop( 'readonly' , true);
        $( "#last_state_change_at" ).prop( 'readonly' , true);


        $( "#switch" ).change(function(){
            setSwitchPort();
        });

        if( $( '#switch_port' ).val() != null ){
            setCustomer();
        }

        /**
         * set data to the customer dropdown when we select a switche port
         * and check if the swich port has a physical interface set and with the possibility to change the status of the physical interface
         */
        $( "#switch_port" ).change(function(){
            setCustomer();
            <?php if( $t->allocating ): ?>
            if( $( "#switch_port").val() != '' ){
                switchPortId = $( "#switch_port" ).val();
                $.ajax( "<?= url( '/api/v4/switch-port' ) ?>/" + switchPortId + "/physical-interface" )
                    .done( function( data ) {
                        if( data.physicalInterfaceFound ) {
                            $( "#pi_status_area" ).show();
                        } else {
                            $( "#pi_status_area" ).hide();
                        }
                    })
                    .fail( function() {
                        alert( "Error running ajax query for switch-port/$id/physical-interface" );
                        $( "#customer" ).html("");
                    })
            }
            <?php endif; ?>
        });

        /**
         * set data to the switch port dropdown when we select a switcher
         */
        function setSwitchPort(){
            $( "#switch_port" ).html( "<option value=\"\">Loading please wait</option>\n" ).trigger( "chosen:updated" );
            switchId = $( "#switch" ).val();
            customerId = $( "#customer" ).val();
            switchPortId = $( "#switch_port_id" ).val();

            $.ajax( "<?= url( '/api/v4/switcher' )?>/" + switchId + "/switch-port", {
                data: {
                    switchId: switchId,
                    custId: $( "#customer" ).val(),
                    spId: $( "#switch_port_id" ).val()
                },
                type: 'POST'
            })
                .done( function( data ) {
                    var options = "<option value=\"\">Choose a switch port</option>\n";
                    $.each( data.listPorts, function( key, value ){
                        options += "<option value=\"" + value.id + "\">" + value.name + " (" + value.type + ")</option>\n";
                    });
                    $( "#switch_port" ).html( options );
                })
                .fail( function() {
                    throw new Error( "Error running ajax query for api/v4/switcher/$id/switch-port" );
                    alert( "Error running ajax query for switcher/$id/customer/$custId/switch-port/$spId" );
                    $( "#customer" ).html("");
                })
                .always( function() {
                    $( "#switch_port" ).trigger( "chosen:updated" );
                });
        }

        /**
         * set data to the customer dropdown
         */
        function setCustomer(){
            if( $( "#switch" ).val() != ''){
                var switchPortId = $( "#switch_port" ).val();
                $( "#customer" ).html( "<option value=\"\">Loading please wait</option>\n" );
                $( "#customer" ).trigger( "chosen:updated" );
                $.ajax( "<?= url( '/api/v4/switch-port' ) ?>/" + switchPortId + "/customer" )
                    .done( function( data ) {
                        if( data.customerFound ) {
                            $( "#customer" ).html( '<option value="' + data.id + '">' + data.name + "</option>\n" );
                        } else {
                            $( "#customer" ).html("");
                        }
                    })
                    .fail( function() {
                        alert( "Error running ajax query for switch-port/$id/customer" );
                        $( "#customer" ).html("");
                    })
                    .always( function() {
                        $( "#customer" ).trigger( "chosen:updated" );
                    });
            }
        }

        /**
         * set data to the switcher dropdown related to the customer selected
         */
        $( "#customer" ).change( function(){
            $( "#switch" ).html( "<option value=\"\">Loading please wait</option>\n" ).trigger( "chosen:updated" );
            $( "#switch_port" ).html("").trigger("chosen:updated");
            customerId = $("#customer").val();

            $.ajax( "<?= url('/api/v4/customer')?>/" + customerId + "/switches", {
                data: {
                    customerId: customerId,
                    patch_panel_id: $( "#patch_panel_id" ).val()
                },
                type: 'POST'
            })
            .done( function( data ) {
                if( data.switchesFound ){
                    var options = "<option value=\"\">Choose a switch</option>\n";
                    $.each( data.switches, function( key, value ){
                        options += "<option value=\"" + key + "\">" + value + "</option>\n";
                    });
                    $( "#switch" ).html( options );
                }
                else{
                    $( "#switch" ).html("");
                }
            })
            .fail( function() {
                throw new Error( "Error running ajax query for api/v4/customer/$id/switches" );
                alert( "Error running ajax query for api/v4/customer/$id/switches" );
            })
            .always( function() {
                $( "#switch" ).trigger( "chosen:updated" );
            });

        });


        /**
         * reset the customer dropdown
         */
        function resetCustomer(){
            options = "<option value=''> Choose a customer</option>\n";
            <?php foreach ( $t->customers as $id => $customer ): ?>
                customer = '<?= $customer ?>';
                options += "<option value=\"" + <?= $id ?> + "\">" + customer  + "</option>\n";
            <?php endforeach; ?>
            $( "#customer" ).html( options ).trigger( "chosen:updated" );
        }

        /**
         * allow to reset the dropdowns (switch/switch port/customer)
         */
        $( ".reset-btn" ).click( function(){
            options = "<option value=''> Choose a Switch</option>\n";
            <?php foreach ( $t->switches as $id => $switch ): ?>
                $switch = '<?= $switch ?>';
                options += "<option value=\"" + <?= $id ?> + "\">" + $switch  + "</option>\n";
            <?php endforeach; ?>
            $( "#switch" ).html( options ).trigger( "chosen:updated" );
            $( "#switch_port" ).html('').trigger( "chosen:updated" );
            resetCustomer();
            $( "#pi_status_area" ).hide();
        });

        /**
         * display / hide help sections on click on the help button
         */
        $( "#help-btn" ).click( function() {
            $( ".help-block" ).toggle();
        });

        var publicNotes  = $( '#notes' );
        var privateNotes = $( '#private_notes' );

        /**
         * Adds a prefix when a user goes to add/edit notes (typically name and date).
         */
        function setNotesTextArea() {
            if( $(this).val() == '' ) {
                $(this).val(notesIntro);
            } else {
                $(this).val( notesIntro  + $(this).val() );
            }
            $(this).setCursorPosition( notesIntro.length );
        }

        /**
         * Removes the prefix added by setNotesTextArea() if the notes where not edited
         */
        function unsetNotesTextArea() {
            $(this).val( $(this).val().substring( notesIntro.length ) );
        }

        // The logic of these two blocks is:
        // 1. if the user clicks on a notes field, add a prefix (name and date typically)
        // 2. if they make a change, remove all the handlers including that which removes the prefix
        // 3. if they haven't made a change, we still have blur / focusout handlers and so remove the prefix
        publicNotes.on( 'focusout', unsetNotesTextArea )
            .on( 'focus', setNotesTextArea )
            .on( 'keyup change', function() { $(this).off('blur focus focusout keyup change') } );

        privateNotes.on( 'focusout', unsetNotesTextArea )
            .on( 'focus', setNotesTextArea )
            .on( 'keyup change', function() { $(this).off('blur focus focusout keyup change') } );

    });
</script>