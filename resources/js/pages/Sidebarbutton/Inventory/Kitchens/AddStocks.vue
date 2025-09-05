<template>
  <form @submit.prevent="submit">
    <div class="mb-4">
      <label class="block mb-1 text-sm font-medium">Stock Name</label>
      <input
        v-model="form.name"
        type="text"
        class="w-full border rounded p-2"
        placeholder="Enter name"
      />
    </div>

    <div class="mb-4">
      <label class="block mb-1 text-sm font-medium">Catigories</label>
      <input
        v-model="form.contact"
        type="text"
        class="w-full border rounded p-2"
        placeholder="Enter Catigories"
      />
    </div>
    
    <div class="mb-4">
      <label class="block mb-1 text-sm font-medium">Quantity</label>
      <input
        v-model="form.contact"
        type="text"
        class="w-full border rounded p-2"
        placeholder="Enter Quantity"
      />
    </div>

    <div class="mb-4">
      <label class="block mb-1 text-sm font-medium">Deliver at</label>
      <input
        v-model="form.contact"
        type="text"
        class="w-full border rounded p-2"
        placeholder="Enter delivey date"
      />
    </div>

    <div class="mb-4">
      <label class="block mb-1 text-sm font-medium">Expite Date</label>
      <input
        v-model="form.contact"
        type="text"
        class="w-full border rounded p-2"
        placeholder="Enter Expire date"
      />
    </div>
    <div class="flex justify-end gap-2">
      <Button type="button" @click="emit('cancel')">Cancel</Button>
      <Button type="submit">Save</Button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

const emit = defineEmits(['cancel', 'saved']);

const form = useForm({
  name: '',
  contact: '',
});

function submit() {
  // replace '/supplier' with your actual backend route
  form.post('/supplier', {
    onSuccess: () => {
      emit('saved');
      form.reset();
    },
  });
}
</script>
