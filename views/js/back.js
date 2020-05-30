/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2020 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

function btnAjax() {
  if (typeof(importpalmira_ajax) != 'undefined') {
    const btn = document.querySelector('#devbtn');
    btn.addEventListener('click', () => {
      const url = window.location.href;
      console.log(url);
      jQuery.ajax({
        type: "POST",
        // headers: { "cache-control": "no-cache" },
        url : importpalmira_ajax,
        dataType: 'json',
        data: {
          // token : 'cb1231baa818f2bc7d15cbb7827a0724'
          ajax: true,
          action: 'changeconfyear',
        },
        success : function(jsonData){
          console.log('success');
          console.log(jsonData);
        }
      });
    });
  }
}

window.onload = function () {
  btnAjax();
};
