const CART_KEY = 'minimal_store_cart';
const COOKIE_KEY = 'guest_cart';
const CHECKOUT_INFO_KEY = 'minimal_store_checkout_info';
const USER_ID = Number(document.body?.dataset?.userId || 0);

const getCookie = (name) => {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
  return null;
};

const setCookie = (name, value, days = 30) => {
  const expires = new Date(Date.now() + days * 86400000).toUTCString();
  document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/; SameSite=Lax`;
};

const readGuestCart = () => {
  try {
    const cookieCart = getCookie(COOKIE_KEY);
    if (cookieCart) {
      const parsed = JSON.parse(cookieCart);
      if (Array.isArray(parsed)) return parsed;
    }
    const local = JSON.parse(localStorage.getItem(CART_KEY));
    return Array.isArray(local) ? local : [];
  } catch {
    return [];
  }
};

const saveGuestCart = (cart) => {
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
  setCookie(COOKIE_KEY, JSON.stringify(cart));
};

const fetchServerCart = async () => {
  const response = await fetch('/api/cart.php');
  const data = await response.json();
  return data.items || [];
};

const addServerCart = async (product) => {
  const response = await fetch('/api/cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'add',
      product_id: product.id,
      variant_id: product.variantId || null,
      quantity: 1,
    }),
  });
  const data = await response.json();
  if (!response.ok || !data.ok) {
    throw new Error(data.message || 'Không thể thêm sản phẩm vào giỏ.');
  }
};

const clearServerCart = async () => {
  await fetch('/api/cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'clear' }),
  });
};

const showToast = (message) => {
  const toast = document.createElement('div');
  toast.className = 'fixed right-4 top-4 z-50 rounded-lg bg-black text-white px-4 py-2 text-sm shadow-lg';
  toast.textContent = message;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 1400);
};

const getCart = async () => (USER_ID ? fetchServerCart() : readGuestCart());

const saveCart = async (cart) => {
  if (!USER_ID) {
    saveGuestCart(cart);
  }
  await updateCartCounter();
  await renderCheckoutItems();
};

const addToCart = async (product) => {
  if (USER_ID) {
    try {
      await addServerCart(product);
      await saveCart([]);
      showToast('Đã thêm vào giỏ (đồng bộ tài khoản).');
    } catch (error) {
      showToast(error.message || 'Thêm giỏ hàng thất bại.');
    }
    return;
  }

  const cart = readGuestCart();
  const existingItem = cart.find((item) => item.id === product.id && item.variantId === (product.variantId || null));
  if (existingItem) {
    existingItem.quantity += 1;
  } else {
    cart.push({ ...product, variantId: product.variantId || null, quantity: 1 });
  }

  await saveCart(cart);
  showToast('Đã thêm sản phẩm vào giỏ hàng.');
};

const updateCartCounter = async () => {
  const cart = await getCart();
  const totalQty = cart.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
  const counter = document.querySelector('#cart-counter');
  if (counter) counter.textContent = `Giỏ hàng: ${totalQty}`;
};

const cartTotal = (cart) => cart.reduce((sum, item) => sum + Number(item.price) * Number(item.quantity), 0);

const renderCheckoutItems = async () => {
  const checkoutContainer = document.querySelector('#checkout-items');
  const totalContainer = document.querySelector('#checkout-total');
  const hiddenInput = document.querySelector('#cart-json');
  if (!checkoutContainer || !totalContainer || !hiddenInput) return;

  const cart = await getCart();
  if (!cart.length) {
    checkoutContainer.innerHTML = '<p class="text-sm text-gray-500">Giỏ hàng đang trống.</p>';
    totalContainer.textContent = '$0.00';
    hiddenInput.value = '[]';
    return;
  }

  checkoutContainer.innerHTML = cart.map((item) => `
    <div class="rounded-lg border border-gray-200 p-3 flex gap-3 items-center">
      <img src="${item.image}" alt="${item.name}" class="h-16 w-16 rounded-lg object-cover" />
      <div class="flex-1">
        <p class="font-medium">${item.name}</p>
        ${item.variant_name || item.variantId ? `<p class="text-xs text-gray-500">Phân loại: ${item.variant_name || 'Biến thể'}</p>` : ''}
        <p class="text-sm text-gray-500">$${Number(item.price).toFixed(2)}</p>
      </div>
      <span class="text-sm">x${item.quantity}</span>
    </div>
  `).join('');

  totalContainer.textContent = `$${cartTotal(cart).toFixed(2)}`;
  hiddenInput.value = JSON.stringify(cart);
};

const loadCheckoutInfo = () => {
  const fields = {
    customer_name: document.querySelector('#customer-name'),
    customer_email: document.querySelector('#customer-email'),
    customer_phone: document.querySelector('#customer-phone'),
    shipping_address: document.querySelector('#shipping-address'),
  };

  if (!fields.customer_name) return;

  try {
    const raw = localStorage.getItem(CHECKOUT_INFO_KEY);
    const data = raw ? JSON.parse(raw) : {};
    Object.entries(fields).forEach(([key, input]) => {
      if (input && !input.value && data[key]) {
        input.value = data[key];
      }
    });
  } catch {
    // noop
  }

  Object.entries(fields).forEach(([key, input]) => {
    if (!input) return;
    input.addEventListener('input', () => {
      try {
        const raw = localStorage.getItem(CHECKOUT_INFO_KEY);
        const data = raw ? JSON.parse(raw) : {};
        data[key] = input.value;
        localStorage.setItem(CHECKOUT_INFO_KEY, JSON.stringify(data));
      } catch {
        // noop
      }
    });
  });
};

document.addEventListener('click', async (event) => {
  const addButton = event.target.closest('.add-to-cart');
  if (!addButton) return;

  const variantSelect = document.querySelector('#variant-select');
  const variantId = variantSelect ? Number(variantSelect.value || 0) || null : null;
  const selectedOption = variantSelect?.selectedOptions?.[0] || null;
  const additionalPrice = Number(selectedOption?.dataset?.additionalPrice || 0);
  const variantName = selectedOption?.dataset?.variantName || null;
  const basePrice = Number(addButton.dataset.price);

  await addToCart({
    id: Number(addButton.dataset.id),
    name: addButton.dataset.name,
    price: basePrice + (variantId ? additionalPrice : 0),
    image: addButton.dataset.image,
    variantId,
    variant_name: variantName,
  });
});

document.addEventListener('DOMContentLoaded', async () => {
  await updateCartCounter();
  await renderCheckoutItems();
  loadCheckoutInfo();

  const successContainer = document.querySelector('#order-success-sync');
  if (successContainer) {
    if (USER_ID) {
      await clearServerCart();
    } else {
      saveGuestCart([]);
      setCookie(COOKIE_KEY, JSON.stringify([]));
    }
    await updateCartCounter();
  }
});
