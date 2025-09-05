<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from "vue";
import { SquareX, SquarePlus } from 'lucide-vue-next';

interface Supplier {
  supplier_id: number;
  supplier_name: string;
}

interface Ingredient {
  ingredient_id: number;
  ingredient_name: string;
  base_unit: string;
}

interface FormItem {
  ingredient_id: number | string;
  item_type: string;
  unit: string;
  quantity: number;
  unit_price: number;
}

interface NewIngredient {
  ingredient_name: string;
  base_unit: string;
  reorder_level: number;
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Inventory', href: '/inventory' },
  { title: 'Stock In', href: '/inventory/stock-in' },
];

const props = defineProps<{
  suppliers: Supplier[];
  ingredients: Ingredient[];
  restaurant_id: number;
}>();

const ingredientsList = ref<Ingredient[]>([...props.ingredients]);
const showModal = ref(false);
const modalProcessing = ref(false);
const modalError = ref("");

interface StockInForm {
  supplier_id: string | number;
  restaurant_id: number;
  reference_no: string;
  items: FormItem[];
  [key: string]: any; 
}

const form = useForm<StockInForm>({
  supplier_id: "",
  restaurant_id: props.restaurant_id,
  reference_no: "",
  items: [],
});

const newIngredient = reactive<NewIngredient>({
  ingredient_name: "",
  base_unit: "kg",
  reorder_level: 0,
});

onMounted(() => {
  if (form.items.length === 0) {
    addItem();
  }
});

function addItem() {
  (form.items ?? []).push({
    ingredient_id: "",
    item_type: "Bulk",
    unit: "kg",
    quantity: 1,
    unit_price: 0,
  });
}

function removeItem(index: number) {
  if ((form.items ?? []).length > 1) {
    (form.items ?? []).splice(index, 1);
  }
}
function setUnitForItem(item: FormItem) {
  const ing = ingredientsList.value.find(i => i.ingredient_id === item.ingredient_id);
  if (ing) {
    item.unit = ing.base_unit;
  }
}

async function saveNewIngredient() {
  modalProcessing.value = true;
  modalError.value = "";
  try {
    const res = await fetch(route("ingredients.store.quick"), {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify(newIngredient),
    });

    const result = await res.json();
    if (!res.ok || result.error) {
      modalError.value = result.error || "Validation failed.";
      modalProcessing.value = false;
      return;
    }

    ingredientsList.value.push(result);
    if ((form.items ?? []).length > 0) {
      (form.items ?? [])[form.items.length - 1].ingredient_id = result.ingredient_id;
    }
    showModal.value = false;
    newIngredient.ingredient_name = "";
    newIngredient.base_unit = "kg";
    newIngredient.reorder_level = 0;
  } catch (e) {
    modalError.value = "Error saving ingredient.";
    console.error("Error saving ingredient:", e);
  }
  modalProcessing.value = false;
}
function resetAndCancel() {
  form.items.forEach(item => {
    item.quantity = 0;
    item.unit_price = 0;
  });
  // Use Inertia's visit method for navigation


  form.visit(route('inventory.index'));
}

const itemTotal = (item: FormItem) => (item.quantity * item.unit_price).toFixed(2);
const orderTotal = () => (form.items ?? []).reduce((total, item) => total + (item.quantity * item.unit_price), 0).toFixed(2);
</script>

<template>
  <Head title="Stock In" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-5xl mx-auto py-10 px-4 sm:px-8 lg:px-12">
  <div class="bg-[#ffffff] rounded-3xl shadow-xl p-8 border border-gray-200">
        

        <!-- Stock-In Form -->
  <form @submit.prevent="form.post(route('stock-in.store'))" class="space-y-8">

          <!-- Supplier & Reference -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
              <label class="block text-base font-semibold text-black mb-2">Supplier</label>
              <select v-model="form.supplier_id" required
                class="w-full border-blue-200 rounded-xl shadow focus:border-blue-400 focus:ring-2 focus:ring-blue-200 py-2 px-3 bg-white">
                <option value="" disabled>Select Supplier</option>
                <option v-for="s in suppliers" :key="s.supplier_id" :value="s.supplier_id">
                  {{ s.supplier_name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-base font-semibold text-black mb-2">Reference Number</label>
              <input v-model="form.reference_no" type="text"
                class="w-full border-blue-200 rounded-xl shadow focus:border-blue-400 focus:ring-2 focus:ring-blue-200 py-2 px-3 bg-white"
                placeholder="Optional reference" />
            </div>
          </div>

          <!-- Items -->
          <div>
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-xl font-bold text-black">Items</h2>
              <div class="flex gap-3">
                <button type="button" @click="showModal = true"
                  class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-white bg-green-500 hover:bg-green-600 shadow transition-all">
                  <SquarePlus class="h-5 w-5" /> Add Ingredient
                </button>
                <button type="button" @click="addItem"
                  class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-white bg-blue-500 hover:bg-blue-600 shadow transition-all">
                  <SquarePlus class="h-5 w-5" /> Add Item
                </button>
              </div>
            </div>

            <div v-for="(item, index) in form.items" :key="index"
              class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start p-6 border border-blue-100 rounded-2xl mb-6 bg-white shadow-sm hover:shadow-lg transition-all">
              
              <!-- Ingredient -->
              <div class="md:col-span-3">
                <label class="block text-sm font-semibold text-black mb-2">Ingredient</label>
                <select v-model="item.ingredient_id" @change="setUnitForItem(item)" required
                    class="w-full border-blue-200 rounded-xl shadow focus:border-blue-400 focus:ring-2 focus:ring-blue-200 py-2 px-3 bg-white">
                    <option value="" disabled>Select Ingredient</option>
                    <option v-for="i in ingredientsList" :key="i.ingredient_id" :value="i.ingredient_id">
                      {{ i.ingredient_name }} ({{ i.base_unit }})
                    </option>
                  </select>
              </div>

              <!-- Type -->
              <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-black mb-2">Type</label>
                <select v-model="item.item_type" required
                  class="w-full border-blue-200 rounded-xl shadow focus:border-blue-400 focus:ring-2 focus:ring-blue-200 py-2 px-3 bg-white">
                  <option value="Bulk">Bulk</option>
                  <option value="Box">Box</option>
                  <option value="Case">Case</option>
                </select>
              </div>

              <!-- Unit -->
              <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-black mb-2">Unit</label>
                <input v-model="item.unit" type="text" placeholder="kg, pcs"
                  class="w-full border-blue-200 rounded-xl shadow focus:border-blue-400 focus:ring-2 focus:ring-blue-200 py-2 px-3 bg-white" required>
              </div>

              <!-- Quantity -->
              <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-black mb-2">Quantity</label>
                <input v-model.number="item.quantity" type="number" min="0.01" step="0.01"
                  class="w-full border-blue-200 rounded-xl shadow focus:border-blue-400 focus:ring-2 focus:ring-blue-200 py-2 px-3 bg-white" required>
              </div>

              <!-- Unit Price -->
              <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-black mb-2">Unit Price</label>
                <input v-model.number="item.unit_price" type="number" min="0" step="0.01"
                  class="w-full border-blue-200 rounded-xl shadow focus:border-blue-400 focus:ring-2 focus:ring-blue-200 py-2 px-3 bg-white" required>
              </div>

              <!-- Remove -->
              <div class="md:col-span-1 flex justify-end">
                <button type="button" @click="removeItem(index)" :disabled="(form.items ?? []).length <= 1"
                  class="mt-8 px-2 py-2 bg-transparent text-black rounded-full hover:bg-red-100 flex items-center transition-all disabled:opacity-50 border-none shadow-none">
                  <SquareX class="h-6 w-6" />
                </button>
              </div>

              <!-- Item Total -->
              <div class="md:col-span-12 pt-4 border-t border-blue-100">
                <p class="text-base font-semibold text-right text-black">
                  Item Total: <span class="text-black">${{ itemTotal(item) }}</span>
                </p>
              </div>
            </div>
          </div>

          <!-- Order Total -->
          <div class="flex justify-end pt-6 border-t border-blue-200">
            <div class="text-2xl font-extrabold text-black">
              Order Total: <span class="text-black">${{ orderTotal() }}</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-6 mt-8">
            <button type="button" @click="resetAndCancel"
              class="px-6 py-3 border border-blue-200 rounded-xl shadow bg-white hover:bg-blue-50 text-blue-700 font-semibold transition-all">
              Cancel
            </button>
            <button type="submit" :disabled="form.processing"
              class="px-6 py-3 rounded-xl text-white bg-blue-600 hover:bg-blue-700 font-bold shadow transition-all disabled:opacity-50">
              {{ form.processing ? 'Processing...' : 'Save Stock-In' }}
            </button>
          </div>
  </form>


        <!-- Ingredient Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showModal = false"></div>
          <div class="relative bg-white rounded-3xl shadow-2xl w-[90%] max-w-lg p-8 z-10 border border-blue-200">
            <h2 class="text-2xl font-extrabold mb-8 text-center text-black">Add New Ingredient</h2>
            <form @submit.prevent="saveNewIngredient" class="space-y-6">
              <div>
                <label class="block text-base font-semibold text-black mb-2">Ingredient Name</label>
                <input v-model="newIngredient.ingredient_name" type="text" required
                  class="w-full border-blue-200 rounded-xl shadow focus:border-blue-400 focus:ring-2 focus:ring-blue-200 py-2 px-3 bg-white" placeholder="e.g., Organic Tomatoes" />
              </div>
              <div>
                <label class="block text-base font-semibold text-black mb-2">Base Unit</label>
                <select v-model="newIngredient.base_unit"
                  class="w-full border-blue-200 rounded-xl shadow focus:border-blue-400 focus:ring-2 focus:ring-blue-200 py-2 px-3 bg-white">
                  <option value="kg">kg</option>
                  <option value="pcs">pcs</option>
                  <option value="box">box</option>
                </select>
              </div>
              <div>
                <label class="block text-base font-semibold text-black mb-2">Reorder Level</label>
                <input v-model.number="newIngredient.reorder_level" type="number" min="0"
                  class="w-full border-blue-200 rounded-xl shadow focus:border-blue-400 focus:ring-2 focus:ring-blue-200 py-2 px-3 bg-white" />
              </div>
              <div v-if="modalError" class="text-red-600 text-sm mb-2">{{ modalError }}</div>
              <div class="flex justify-end gap-4 pt-6">
                <button type="button" @click="showModal = false" :disabled="modalProcessing"
                  class="px-6 py-2 rounded-xl border border-blue-200 text-blue-700 bg-white hover:bg-blue-50 font-semibold transition-all">Cancel</button>
                <button type="submit" :disabled="modalProcessing"
                  class="px-6 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 font-bold shadow transition-all disabled:opacity-50">
                  {{ modalProcessing ? 'Saving...' : 'Save Ingredient' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
