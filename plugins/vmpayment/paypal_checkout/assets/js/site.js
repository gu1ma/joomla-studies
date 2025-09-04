
/*if (typeof vmPP === "undefined")
    var vmPP = {};*/



jQuery(function($) {

    Virtuemart.renderPayPalButtons = function (ppButtonContainer) {
        if (typeof paypal.Buttons !== "undefined") {
            paypal.Buttons({

                style: vmPPStyle,
                // onInit is called when the button first renders
                onInit(data, actions) {

                    vmPP.checked = true;

                    if (vmPP.view == 'cart') {
                        checkoutButton = $("#checkoutFormSubmit");
                        //console.log('my button',checkoutButton);
                        if (vmPP.task != "getUserInfo" && checkoutButton[0].name != 'confirm') {
                            actions.disable();
                        }

                        if(vmPP.selected == 'buttons' && vmPP.bt_checkout_txt!="0"){
                            if(vmPP.debug == '1'){ console.log('Buttons selected');}
                            if(vmPP.vmPPOrderId =='') {
                                //checkoutButton[0].setAttribute("disabled", "disabled");
                                //$(checkoutButton[0]).text(vmPP.bt_checkout_txt);
                                //$(checkoutButton[0]).off('click');
                                //$(checkoutButton[0]).on('click',function(event){paypal.Buttons.onClick(event)})
                            } else {

                                //checkoutButton[0].removeAttribute("disabled");
                                //$('.vm-payment-select').hide();
                            }
                        } else {
                            //checkoutButton[0].removeAttribute("disabled");
                        }
                    }

                },

                // onClick is called when the button is clicked
                onClick(event) {
                    if (vmPP.view == 'productdetails') {

                        checkoutForm = $("form.product");
                        //checkoutForm.append('<input type="hidden" name="virtuemart_paymentmethod_id" value="'+vmPP.methodId+'">');
                        //checkoutForm.append('<input type="hidden" name="task" value="addJS" />');
                        console.log('my checkoutForm', checkoutForm);

                        $(checkoutForm[0]).unbind('submit');
                        $(checkoutForm[0]).bind('submit', function (event) {
                            event.preventDefault();
                            if(vmPP.debug == '1') {console.log('my event', event);}
                            return new Promise((resolve, reject) => {
                                $.ajax({
                                    url: Virtuemart.vmSiteurl + "index.php?option=com_virtuemart&view=cart&task=addJS&format=json",
                                    type: "POST",
                                    cache: false,
                                    async: false,
                                    data: $(checkoutForm[0]).serialize(),
                                    success: function (data) {
                                        console.log('Product added ', data);
                                        return resolve(true);
                                    },
                                    error: function (data) {
                                        console.log('Error ', data);
                                        return reject(false);
                                    }
                                }).done(function (data) {
                                    return resolve(true);
                                });
                            })
                        });
                        return new Promise((resolve, reject) => {
                            if ($(checkoutForm[0]).submit()) {
                                return resolve(true);
                            }

                        })
                        /*if(vmPP.task == "getUserInfo"){
                            checkoutForm.submit();
                            //add Product, execute getUserInfo, redirect to cart
                        } else {
                            //add Product, redirect to cart with paymentId to set Paypal directly
                        }*/
                    } else {
                        if (vmPP.task != "getUserInfo" && checkoutButton[0].name != 'confirm') {

                            alert('Please confirm the TOS first');
                        }
                    }


                },

                //Returns OrderId in response
                createOrder() {
                    if(vmPP.debug == '1') {console.log('Paypal createOrder ' + vmPP.task);}
                    return Virtuemart.createOrder(vmPP.button_pm_id);

                },

                //Create order
                // Approve order
                // Get order detais
                // Redirect buyer
                // Capture order
                onApprove(data) {
                    //Virtuemart.startVmLoading();
                    $(this).vm2front("startVmLoading");
                    //alert('Please wait while capturing the order');
                    // This function captures the funds from the transaction.
                    //if (vmPP.task == "getUserInfo"){
                    $.ajax({
                        type: "POST",
                        cache: false,
                        dataType: "json",
                        /*data: config,*/
                        url: Virtuemart.vmSiteurl + "index.php?option=com_virtuemart&view=plugin&vmtype=vmpayment&name=paypal_checkout&task=" + vmPP.task + "&pm=" + vmPP.button_pm_id + "&id=" + vmPP.vmPPOrderId,
                    }).done(
                        function (data) {
                            if (vmPP.task == "getUserInfo") {
                                if(vmPP.debug == '1') {console.log("getUserInfo data",data);}
                                window.location.href = Virtuemart.vmSiteurl + "index.php?option=com_virtuemart&view=cart&task=checkout";
                                return data;
                            } else {

                                $(this).vm2front("stopVmLoading");
                                if (data != false && data !='') {
                                    window.location.href = Virtuemart.vmSiteurl + "index.php?option=com_virtuemart&view=plugin&vmtype=vmpayment&name=paypal_checkout&task=ordercompleted";
                                    //window.location.href = Virtuemart.vmSiteurl + "index.php?option=com_virtuemart&view=cart&layout=orderdone";
                                    if(vmPP.debug == '1') {console.log('onApprove ',data);}
                                    //alert('Thank your your order');
                                } else {
                                    console.log('onApprove ',data);
                                    alert(vmPP.bt_error_txt);
                                }
                            }

                        }
                    );

                },

                onCancel(data) {
                    console.log('Got Cancelled PPButton', data);
                    Virtuemart.emptyOrderId(false);
                },
                //triggered by header 204 and higher
                onError(err) {
                    console.log('Got error PPButton', err);
                    Virtuemart.emptyOrderId(true);
                }

            }).render(ppButtonContainer);

        }
    },

    Virtuemart.renderPayPalCredit = function(){

        if(typeof paypal.HostedFields !== "undefined"){
            //PayPal Advanced Creditcards
            //add listener to own button and execute
            if (paypal.HostedFields.isEligible()) {
                let orderId;
                if(vmPP.debug == '1') {console.log('Paypal Hosted Fields eligible');}
                if(vmPP.selected == 'hosted-fields' && vmPP.task != 'getUserInfo') {
                    jQuery('.card_container').show();
                } else {
                    jQuery('.card_container').hide();
                    $("#checkoutForm").off('submit');
                    if(vmPP.debug == '1') {console.log('Paypal Hosted Fields hidden, return');}

                    return;
                }

                // Renders card fields
                paypal.HostedFields.render({
                    // Call your server to set up the transaction
                    createOrder: () => {

                        return Virtuemart.createOrder(vmPP.methodId);
                    },
                    //styles: vmPP.bt_styles,
                    fields: {
                        number: {
                            selector: '#card-number',
                            placeholder: '4111 1111 1111 1111',
                        },
                        cvv: {
                            selector: '#cvv',
                            placeholder: 'card security number',
                        },
                        expirationDate: {
                            selector: '#expiration-date',
                            placeholder: 'MM/YY',
                        },
                    },
                }).then(function (hf) {

                    hf.on('validityChange', function (event) {

                        checkoutButton = $("#checkoutFormSubmit");
                        if(vmPP.debug == '1'){console.log('my stuff here ',event.fields);}
                        if(event.fields.cvv.isValid  && event.fields.expirationDate.isValid  && event.fields.number.isValid ){
                            if(vmPP.debug == '1'){console.log('Credit card data form valid');}
                            checkoutButton[0].removeAttribute("disabled");
                            $(checkoutButton[0]).text(vmPP.BtConfirmTxt);
                        } else {
                            if(vmPP.debug == '1'){console.log('Credit card data form invalid');}
                            checkoutButton[0].setAttribute("disabled", "disabled");
                            $(checkoutButton[0]).text(vmPP.hosted_txt);
                        }
                    });
                    Virtuemart.checkCreditData(hf);

                    if(vmPP.task != 'getUserInfo'){
                        if(vmPP.debug == '1'){console.log('Hosted Fields takes button');}

                        //$("#checkoutForm").off('submit');
                        $("#checkoutFormSubmit").off('click');
                        $("#checkoutFormSubmit").on('click', function (event) {

                            console.log(event);

                            event.preventDefault();
                            hf.submit({

                                contingencies: ['3D_SECURE'],

                                // Cardholder Name
                                cardholderName: document.getElementById('card-holder-name').value,
                                // Billing Address
                                billingAddress: {
                                    streetAddress: document.getElementById('card-billing-address-street').value,      // address_line_1 - street
                                    extendedAddress: document.getElementById('card-billing-address-unit').value,       // address_line_2 - unit
                                    region: document.getElementById('card-billing-address-state').value,           // admin_area_1 - state
                                    locality: document.getElementById('card-billing-address-city').value,          // admin_area_2 - town / city
                                    postalCode: document.getElementById('card-billing-address-zip').value,           // postal_code - postal_code
                                    countryCodeAlpha2: document.getElementById('card-billing-address-country').value   // country_code - country
                                }
                            }).then(
                                function(payload)
                                {

                                    console.log('my payload ',payload);

                                    $.ajax({
                                        type: "POST",
                                        cache: false,
                                        dataType: "json",
                                        /*data: config,*/                                                                   // check auf 3ds, Daten in response, wenn erfolgreich, capture
                                        url: Virtuemart.vmSiteurl + "index.php?option=com_virtuemart&view=plugin&vmtype=vmpayment&name=paypal_checkout&task=checkDs&pm=" + vmPP.methodId,
                                    }).done( function(data) {

                                        $(this).vm2front("stopVmLoading");
                                        if (data != false && data !='') {
                                            window.location.href = Virtuemart.vmSiteurl + "index.php?option=com_virtuemart&view=plugin&vmtype=vmpayment&name=paypal_checkout&task=ordercompleted";
                                            //window.location.href = Virtuemart.vmSiteurl + "index.php?option=com_virtuemart&view=cart&layout=orderdone";
                                            if(vmPP.debug == '1') {console.log('onApprove ',data);}
                                            //alert('Thank your your order');
                                        } else {
                                            if(vmPP.debug == '1') {console.log('onApprove ',data);}
                                            Virtuemart.emptyOrderId(true);
                                            alert('Something went wrong capturing the payment');
                                        }
                                        //window.location.href = Virtuemart.vmSiteurl + "index.php?option=com_virtuemart&view=cart";

                                    }).fail(function(err) {
                                        if(vmPP.debug == '1') {console.log(vmPP.hosted_error_txt,err);}
                                        alert(vmPP.hosted_error_txt);
                                    });
                                }
                            )
                        });


                    } else {
                        if(vmPP.debug == '1'){console.log('Hosted Fields inactive');}
                    }

                });
            } else {
                // Hides card fields if the merchant isn't eligible
                if(vmPP.debug == '1')console.log('Paypal Hosted Fields Not eligible');
                //$('.card_container').hide();
            }
        } else {
            console.log('Paypal Hosted Fields not defined');
        }

    },

    Virtuemart.createOrder = function(pmId) {
        return new Promise((resolve, reject) => {
            $.ajax({
                type: "POST",
                cache: false,
                dataType: "json",
                /*data: config,*/
                url: Virtuemart.vmSiteurl + "index.php?option=com_virtuemart&view=plugin&vmtype=vmpayment&name=paypal_checkout&task=createOrder&btask=" + vmPP.task + "&pm=" + pmId,
            }).done(
                function (data) {
                    if(vmPP.debug == '1') {
                        console.log('createOrder done ',data);
                        //console.trace()
                    }
                    vmPP.vmPPOrderId = data.id;
                    return resolve(data.id);
                });
        })
    },

    Virtuemart.checkCreditData = function (hostedFieldsInstance){
        var state = hostedFieldsInstance.getState();
        var formValid = Object.keys(state.fields).every(function (key) {
            return state.fields[key].isValid;
        });
        checkoutButton = $("#checkoutFormSubmit");
        if (formValid) {
            checkoutButton[0].removeAttribute("disabled");
            $(checkoutButton[0]).text(vmPP.BtConfirmTxt);
        } else {
            checkoutButton[0].setAttribute("disabled", "disabled");
            $(checkoutButton[0]).text(vmPP.hosted_txt);
        }
    },

    Virtuemart.emptyOrderId = function() {

        vmPP.vmPPOrderId = '';
        $.ajax({
            type: "GET",
            cache: false,
            dataType: "json",
            /*data: config,*/
            url: Virtuemart.vmSiteurl + "index.php?option=com_virtuemart&view=plugin&vmtype=vmpayment&name=paypal_checkout&task=emptyOrderId&pm="+vmPP.methodId,
        }).done(function (data) {

                if(vmPP.debug == '0'){
                    //window.location.href = "index.php?option=com_virtuemart&view=cart&layout=default";
                } else {
                    console.log('PayPal OrderId deleted');
                }
            }
        );
    },

    Virtuemart.renderPUI = function () {
        paypal.Legal({
            fundingSource: paypal.Legal.FUNDING.PAY_UPON_INVOICE,
        })
            .render("#paypal-pui-container");

        if(vmPP.selected == 'pui') {
            console.log('PUI selected');

            $("#phone_1_field").off('input',Virtuemart.checkPUIData);
            $("#phone_1_field").on('input',Virtuemart.checkPUIData);
            $("#paypal_date_of_birth").off('input change',Virtuemart.checkPUIData);
            $("#paypal_date_of_birth").on('input change',Virtuemart.checkPUIData);
            $("#paypal_int_number").off('input',Virtuemart.checkPUIData);
            $("#paypal_int_number").on('input',Virtuemart.checkPUIData);

            Virtuemart.checkPUIData();
        }
    },

    Virtuemart.checkPUIData = function () {

        var checkoutButton = $('#checkoutFormSubmit');

        checkoutButton[0].setAttribute("disabled", "disabled");
        $(checkoutButton[0]).text('Please fill the missing data');
        if(vmPP.debug == '1') {
            console.log("Paypal.pui loaded and Button disabled", $("#phone_1_field").val(), $("#paypal_date_of_birth").val(), $("#paypal_int_number").val());
        }
        if( $("#phone_1_field").val() != '' &&
            $("#paypal_date_of_birth").val() != '0' &&
            $("#paypal_int_number").val() != '+') {
            checkoutButton[0].removeAttribute("disabled");
            $(checkoutButton[0]).text(vmPP.pui_checkout_txt);
            if(vmPP.debug == '1') {
                console.log("Paypal.pui loaded and Button enabled ",$("#paypal_date_of_birth").val());
            }
        } else {
            //We could do the border red here
            if( $("#phone_1_field").val() == '') {Virtuemart.makeRedBorder("#phone_1_field")};
            if( $("#paypal_date_of_birth").val() == '') {Virtuemart.makeRedBorder("#paypal_date_of_birth")};
            if( $("#paypal_int_number").val() == '+') {Virtuemart.makeRedBorder("#phone_1_field")};
        }
    },

    Virtuemart.makeRedBorder = function(selector){
        $(selector).parent().css("border-color", "red");
        $(selector).parent().css("border-style", "solid");
        $(selector).parent().css("border-width", "1px");
    },

    Virtuemart.onReadyPP = function (){

        $.ajaxSetup('Access-Control-Allow-Origin: *.paypal.com');

        if(vmPP.debug == '1') {console.log("with vmPPStyle, vmPP", vmPPStyle, vmPP);}
            if(vmPP.products.indexOf('buttons') >=0 || vmPP.products.indexOf('hosted-fields') >=0 || vmPP.products.indexOf('pui') >=0 ){
                killme = setInterval(function(){

                    if(typeof paypal !== "undefined" ){

                        clearInterval(killme);
                        clearTimeout(finalPunisher);

                        if(vmPP.debug == '1') {console.log("Paypal loaded");}
                        if(vmPP.vmPPOrderId=="" && vmPP.products.indexOf('buttons') >=0){
                            if(vmPP.debug == '1') {console.log("Paypal buttons loaded");}
                            if(vmPP.withButton == "true")Virtuemart.renderPayPalButtons("#paypal-button-container");

                            if(vmPP.withLogin == "true"){
                                Virtuemart.renderPayPalButtons("#paypal-button-login");
                            }
                        }

                        if(vmPP.products.indexOf('hosted-fields') >=0 && vmPP.selected == 'hosted-fields'){
                            if(vmPP.debug == '1') {console.log("Paypal hosted-fields loaded");}
                            // Hides card fields if the merchant isn't eligible
                            jQuery('.card_container').hide();
                            Virtuemart.renderPayPalCredit();
                        }

                        if(vmPP.products.indexOf('pui') >=0){
                            if(vmPP.debug == '1') {console.log("Paypal PUI loaded");}
                            Virtuemart.renderPUI();
                        }

                    } else {
                        if(vmPP.debug == '1'){
                            console.log("Paypal not loaded yet ");
                        }

                        fetchButtonSDK();
                    }
                },100);

                finalPunisher = setTimeout(function(){
                    clearInterval(killme);
                    console.log("Loading Buttons killed");
                },60000);



        }
    }

    async function fetchButtonSDK() {
        const response = await fetch(vmPP.nvpUrl);
        console.log("Paypal not loaded yet fetchButtonSDK ",response);
    }
})

