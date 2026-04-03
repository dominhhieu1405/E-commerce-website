const CART_KEY = 'minimal_store_cart';

const readCart = () => {
  try {
    const parsed = JSON.parse(localStorage.getItem(CART_KEY));
    return Array.isArray(parsed) ? parsed : [];
  } catch (error) {
    return [];
  }
};

const saveCart = (cart) => {
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
  updateCartCounter();
  renderCheckoutItems();
};

const showToast = (message) => {
  const toast = document.createElement('div');
  toast.className = 'fixed right-4 top-4 z-50 rounded-lg bg-black text-white px-4 py-2 text-sm shadow-lg';
  toast.textContent = message;
  document.body.appendChild(toast);

  setTimeout(() => {
    toast.classList.add('opacity-0', 'transition');
  }, 1200);

  setTimeout(() => {
    toast.remove();
  }, 1600);
};

const addToCart = (product) => {
  const cart = readCart();
  const existingItem = cart.find((item) => item.id === product.id);

  if (existingItem) {
    if (existingItem.quantity >= product.stock) {
      showToast('Đã đạt số lượng tối đa trong kho.');
      return;
    }
    existingItem.quantity += 1;
  } else {
    cart.push({ ...product, quantity: 1 });
  }

  saveCart(cart);
  showToast('Đã thêm sản phẩm vào giỏ hàng.');
};

const updateCartCounter = () => {
  const cart = readCart();
  const totalQty = cart.reduce((sum, item) => sum + item.quantity, 0);
  const counter = document.querySelector('#cart-counter');

  if (counter) {
    counter.textContent = `Giỏ hàng: ${totalQty}`;
  }
};

const changeQuantity = (id, direction) => {
  const cart = readCart();
  const item = cart.find((entry) => entry.id === id);

  if (!item) return;

  const nextQuantity = item.quantity + direction;

  if (nextQuantity <= 0) {
    const filtered = cart.filter((entry) => entry.id !== id);
    saveCart(filtered);
    return;
  }

  if (nextQuantity > item.stock) {
    showToast('Số lượng vượt quá tồn kho.');
    return;
  }

  item.quantity = nextQuantity;
  saveCart(cart);
};

const cartTotal = (cart) => cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

const renderCheckoutItems = () => {
  const checkoutContainer = document.querySelector('#checkout-items');
  const totalContainer = document.querySelector('#checkout-total');
  const hiddenInput = document.querySelector('#cart-json');

  if (!checkoutContainer || !totalContainer || !hiddenInput) {
    return;
  }

  const cart = readCart();

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
        <p class="text-sm text-gray-500">$${Number(item.price).toFixed(2)}</p>
      </div>
      <div class="flex items-center gap-2">
        <button type="button" class="rounded border px-2 py-1" data-action="minus" data-id="${item.id}">-</button>
        <span class="text-sm min-w-[20px] text-center">${item.quantity}</span>
        <button type="button" class="rounded border px-2 py-1" data-action="plus" data-id="${item.id}">+</button>
      </div>
    </div>
  `).join('');

  totalContainer.textContent = `$${cartTotal(cart).toFixed(2)}`;
  hiddenInput.value = JSON.stringify(cart);
};

document.addEventListener('click', (event) => {
  const addButton = event.target.closest('.add-to-cart');
  if (addButton) {
    addToCart({
      id: Number(addButton.dataset.id),
      name: addButton.dataset.name,
      price: Number(addButton.dataset.price),
      image: addButton.dataset.image,
      stock: Number(addButton.dataset.stock),
    });
    return;
  }

  const quantityButton = event.target.closest('[data-action]');
  if (quantityButton) {
    const id = Number(quantityButton.dataset.id);
    const action = quantityButton.dataset.action;
    changeQuantity(id, action === 'plus' ? 1 : -1);
  }
});

document.addEventListener('DOMContentLoaded', () => {
  updateCartCounter();
  renderCheckoutItems();
});
