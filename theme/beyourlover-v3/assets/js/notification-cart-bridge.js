/* BYL N6 — Store API confirmed-response to short Toast adapter.
 * Never issues a request, changes Woo, handles payment, or hides an error.
 * Script is enqueued only for Dev dual opt-in Cart preview.
 */
(function (global) {
  'use strict';
  const notify = global.BYL && global.BYL.notify;
  if (typeof notify !== 'function') return;
  const rows = cart => Array.isArray(cart?.items) ? cart.items : [];
  const codes = cart => Array.isArray(cart?.coupons) ? cart.coupons
    .map(x => String(typeof x === 'string' ? x : x?.code || '').trim().toLowerCase()) : [];
  const totalProduct = (cart,id) => rows(cart).reduce((n,row) =>
    n + (Number(row.id) === Number(id) ? Number(row.quantity)||0 : 0), 0);
  const findKey = (cart,key) => rows(cart).find(x=>String(x.key)===String(key));
  const isValid = cart => cart && typeof cart === 'object' && Array.isArray(cart.items);
  const allow = Object.freeze({
    'add-item':'Added to your cart',
    'remove-item':'Removed from your cart',
    'update-item':'Quantity updated',
    'apply-coupon':'Promo code applied',
    'remove-coupon':'Promo code removed'
  });
  function confirm({endpoint,payload,before,after} = {}) {
    if (!Object.prototype.hasOwnProperty.call(allow,endpoint) ||
        !payload || typeof payload!=='object' || !isValid(after) || !isValid(before)) return false;
    let accepted = false, key = '';
    const id=Number(payload.id), lineKey=String(payload.key||''), qty=Number(payload.quantity);
    const coupon=String(payload.code||'').trim().toLowerCase();
    switch(endpoint) {
      case 'add-item':
        if (!Number.isSafeInteger(id) || id<=0) return false;
        accepted=totalProduct(after,id)>totalProduct(before,id);
        key=String(id);break;
      case 'remove-item':
        accepted=!!lineKey && !!findKey(before,lineKey) && !findKey(after,lineKey);
        key=lineKey;break;
      case 'update-item':
        accepted=!!lineKey && Number.isSafeInteger(qty) && qty>=1 &&
          Number(findKey(after,lineKey)?.quantity)===qty &&
          Number(findKey(before,lineKey)?.quantity)!==qty;
        key=lineKey;break;
      case 'apply-coupon':
        accepted=!!coupon && !codes(before).includes(coupon) && codes(after).includes(coupon);
        key=coupon;break;
      case 'remove-coupon':
        accepted=!!coupon && codes(before).includes(coupon) && !codes(after).includes(coupon);
        key=coupon;break;
    }
    if (!accepted) return false;
    // Only fixed, reviewed copy reaches the UI; no backend HTML is interpolated.
    return notify({type:'success',confirmed:true,context:'cart-drawer',
      key:endpoint+':'+key,message:allow[endpoint]});
  }
  global.BYL.cartNoticeBridge=Object.freeze({confirm, version:'1.0.0'});
})(window);
