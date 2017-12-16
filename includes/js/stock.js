jQuery(function ($) {

    if (!$(document.body).hasClass('shop-settings'))
        return;

    var dynamicPricingGroupTpl = $('#dynamic-pricing-group-template').html(),
        $dynamicPricingGroupContainer = $('.dynamic-pricing-group-container'),
        dynamicPricingGroupLimit = window.dynamicPricingGroupLimit || 4;

    $('.nims-price-depends').change(function (e) {

        if (this.value && !countPricingGroups()) {
            addDynamicPricingGroup(window.minNimAmount || 5000);
        }


        $('#nims-price-static').find('#nim_price').attr('required', function (_, attr) {
            return !attr
        }).end()
            .toggleClass('hidden', this.value);

        $dynamicPricingGroupContainer.toggleClass('hidden', this.value);
    });

    $dynamicPricingGroupContainer
        .on('click', '.add-pricing-rule', function (e) {
            e.preventDefault();
            addDynamicPricingGroup();
        })
        .on('click', '.delete-pricing-rule', function (e) {
            e.preventDefault();

            if (countPricingGroups() > 1) {
                $(this).closest('.dynamic-pricing-group').remove();
                togglePricingGroupBtn();
                $dynamicPricingGroupContainer.find('.dynamic-pricing-group:last-child .nim-quantity-max').removeAttr('readonly');
            }
        });

    function addDynamicPricingGroup(value) {
        if (countPricingGroups() >= dynamicPricingGroupLimit) {
            alert('Вы добавили максимальное количество ценновых групп.');
            return false;
        }


        var $lastDynamicPricingGroup = $dynamicPricingGroupContainer.find('.dynamic-pricing-group:last-child');

        if ($lastDynamicPricingGroup.length) {

            var lastMinVal = $lastDynamicPricingGroup.find('.nim-quantity-min').val();
            $lastDynamicPricingGroup.find('.nim-quantity-max').attr('data-parsley-gt', lastMinVal);


            var errors = [];
            $lastDynamicPricingGroup.find('input').each(function (i, el) {
                errors.push($(el).parsley().validate());
            });

            var errorExist = false;
            errors.forEach(function (errObj) {
                if (errObj !== true) {
                    errorExist = true;
                }
            });

            if (errorExist) {
                return false;
            }

            $lastDynamicPricingGroup.find('.add-pricing-rule, .delete-pricing-rule').remove();
            $lastDynamicPricingGroup.find('.nim-quantity-max').attr('readonly', 'readonly');
            value = +$lastDynamicPricingGroup.find('.nim-quantity-max').val() + 1;
        }

        var tpl = dynamicPricingGroupTpl.replace(/\[]/g, '[new_' + countPricingGroups() + ']');
        var $newPricingGroup = $(tpl);
        $dynamicPricingGroupContainer.append($newPricingGroup);
        $newPricingGroup.find('.nim-quantity-min').val(value);
        togglePricingGroupBtn();
    }

    function countPricingGroups() {
        return $dynamicPricingGroupContainer.find('.dynamic-pricing-group').length;
    }

    function togglePricingGroupBtn() {
        var $firstDynamicPricingGroup = $('.dynamic-pricing-group:first-child');
        var $lastDynamicPricingGroup = $('.dynamic-pricing-group:last-child');

        if (!$lastDynamicPricingGroup.find('.add-pricing-rule').length
            && countPricingGroups() < dynamicPricingGroupLimit) {

            var $lastDynamicPricingGroupDeleteBtn = $lastDynamicPricingGroup.find('.delete-pricing-rule');
            if ($lastDynamicPricingGroupDeleteBtn.length > 0) {
                $('<a class="add-pricing-rule"></a>').insertBefore($lastDynamicPricingGroupDeleteBtn);
            } else {
                $lastDynamicPricingGroup.append($('<a class="add-pricing-rule"></a> '));
            }


        } else if (countPricingGroups() >= dynamicPricingGroupLimit) {
            $lastDynamicPricingGroup.find('.add-pricing-rule').remove();
        }

        if (countPricingGroups() === 1) {
            $firstDynamicPricingGroup.find('.delete-pricing-rule').remove();
        } else if (!$lastDynamicPricingGroup.find('.delete-pricing-rule').length) {
            $lastDynamicPricingGroup.append('<a class="delete-pricing-rule"></a>');
        }

    }

    $('form[name="user-shop-settings-form"]').parsley().on('form:validate', function (formInstance) {
        if ($('.nims-price-depends:checked', this.element).val() == 1 && $('.dynamic-pricing-group').length == 1) {
            alert("Вы не можете добавить одну ценновую группу. Добавьте ценновые группы или используйте фиксированную цену");
            formInstance.validationResult = false;
        }
    });
});

/*jQuery(document).ready(function ($) {

    jQuery.fn.preventDoubleSubmission = function () {
        $(this).on('submit', function (e) {
            var $form = $(this);

            if ($form.data('submitted') === true) {
                // Previously submitted - don't submit again
                alert('Форма уже была отправлена. Пожалуйста, подождите.');
                e.preventDefault();
            } else {
                // Mark it so that the next submit can be ignored
                // ADDED requirement that form be valid
                // if($form.valid()) {
                $form.data('submitted', true);
                // }
            }
        });

        // Keep chainability
        return this;
    };

    $('form').preventDoubleSubmission();
});*/

jQuery(document).ready(function ($) {

    updateNimShops($);

    var memberList = document.getElementById('members-dir-list');
    if (memberList != undefined) {

        MutationObserver = window.MutationObserver || window.WebKitMutationObserver;

        if ( MutationObserver != undefined) {

            var observer = new MutationObserver(function(mutations, observer) {
                // fired when a mutation occurs
                // console.log(mutations, observer);
                updateNimShops($);
            });
            // for ios 6 compatibility, see http://caniuse.com/#feat=mutationobserver
            memberList.addEventListener('DOMNodeInserted', function () {});
            observer.observe( memberList, { childList: true } );
        }
    }
});

function updateNimShops($) {
    var shopPriceRanges = [];

    $('.user-shop').each(function (index, shop) {
        var $shop = $(shop);
        var shopId = $shop.attr('data-shop-id');
        var shopPriceDynamic = $shop.hasClass('price-depends');

        if (shopPriceDynamic) {
            shopPriceRanges[shopId] = [];

            $shop.find('.user-shop-pricing-groups > div').map(function (i, el) {
                shopPriceRanges[shopId][i] = {
                    minQuantity: $(el).find('.pg-min-quantity').data('min-quantity'),
                    maxQuantity: $(el).find('.pg-max-quantity').data('max-quantity'),
                    price: $(el).find('.pg-price').data('price')
                }
            });
        }

        $shop.find('.shop-nim-amount').on('keyup change', function () {
            var amount = this.value;
            amount = amount ? +amount : 0;
            var $shopPriceElem = $shop.find('.shop-price-amount');
            var price = $shopPriceElem.attr('data-price-amount');

            if (shopPriceDynamic) {
                shopPriceRanges[shopId].forEach(function (range) {
                    if (amount >= range['minQuantity']) {
                        price = +range['price'];
                    }
                });

                var priceText = price.toLocaleString('ru-RU', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).replace(',', '.');

                $shopPriceElem.text(priceText);
            } // end of Dynamic pricing

            var $shopPriceTotalAmountElem = $shop.find('.shop-price-total-amount');
            var totalAmount = amount * price / 1000;
            var totalAmountText = totalAmount.toLocaleString('ru-RU', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).replace(',', '.');
            $shopPriceTotalAmountElem.text(totalAmountText);

        });
    });
}

jQuery( window ).load(function() {
    if (jQuery('body').hasClass('activate') && jQuery('#activate-page .stockexchange-account-activated').length > 0) {
        if (window.yaCounter45052823 != undefined) {
            window.yaCounter45052823.reachGoal('user_register');
        }
    }
});



jQuery(document).ready(function ($) {

    // mobile menu
    $('.menu-toggle').click(function () {

        $('ul').toggleClass('opening');
        $(this).toggleClass('open');

    });

    $('.wallet-select input[type="radio"][name="wallet"]').change(function () {
        var minWithdraw = $(this).attr('data-withdraw-min');
        $('#withdraw_amount').attr('min', minWithdraw).attr('placeholder', 'мин ' + minWithdraw);
    });

    $('#form-topup').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this),
            $topupSubmit = $form.find('#topup-submit');
        var topupAmount = $('#topup-amount').val();

        $form.attr('disabled', 'disabled');

        $topupSubmit.attr('disabled', 'disabled').text("Загрузка").addClass('loading');

        $.post(ajaxurl, {
            action: 'get_fk_signature',
            topup_amount: topupAmount
        }, function (data) {
            if (data && data.success != undefined) {
                $('#topup-fk-signature').val(data.success);
                $form.off('submit').submit();
            } else {
                alert(data && data.error != undefined ? data.error : "Неизвестная ошибка. Попробуйте снова");
                $form.removeAttr('disabled');
                $topupSubmit.removeAttr('disabled').text('Оплатить').removeClass('loading');
            }

        }, 'json');
    });


    window.updateUserBalance = function (e) {
        e.preventDefault();

        if (window.updatingUserBalance) {
            return;
        }

        window.updatingUserBalance = true;

        var $refreshBtn = $('#refresh-btn');
        $refreshBtn.addClass('refresh-btn-loading');

        $.post(ajaxurl, {action: 'update_user_balance'}, function (data) {
                if (data.balance !== false && data.balance !== undefined) {
                    $('.bp-balance-nav > a').text(data.balance);
                    $('.user-balance').text(data.balance);

                    data.rawBalance = +data.rawBalance;
                    if (data.rawBalance >= window.orderTotal) {
                        $('#topup-note').remove();
                        $('#confirm-order-submit').removeAttr('disabled');
                        $refreshBtn.remove();
                    } else {
                        var orderSumDiff = window.orderTotal - data.rawBalance;
                        var topupHref = $('#topup-link').attr('href');

                        $('#topup-link').attr('href', topupHref.replace(/\?topup=.*$/, '?topup=' + orderSumDiff));

                        $('#topup-amount').html(orderSumDiff.toLocaleString('ru-RU', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }).replace(',', '.') + ' &#8381;');
                    }
                } else {
                    alert("При обновлении баланса произошла ошибка! " +
                        "Пожалуйста, попробуйте снова или перезагрузите страницу");
                }

                $refreshBtn.removeClass('refresh-btn-loading');
                window.updatingUserBalance = false;
            }, 'json'
        );

        return false;
    }

    $('#refresh-btn').click(updateUserBalance);
});

// Greater than validator
window.Parsley.addValidator('gt', {
    validateString: function (value, requirement) {
        return parseFloat(value) > parseFloat(requirement);
    },
    messages: {
        en: "This value should be greater than min. quantity",
        ru: "Это значение должно быть больше чем мин. кол-во"
    },
    priority: 32
});

window.Parsley.addValidator("requiredIf", {
    validateString: function (value, requirement) {
        if (jQuery(requirement).val()) {
            return !!value;
        }

        return true;
    },
    priority: 33
});

window.Parsley.addValidator("patternIf", {
    validateString: function (value, pattern) {
        if (value) {
            return new RegExp(pattern).test(value);
        }

        return true;
    },
    messages: {
        en: "Incorrect value format",
        ru: "Неправильный формат"
    },
    priority: 34
});
