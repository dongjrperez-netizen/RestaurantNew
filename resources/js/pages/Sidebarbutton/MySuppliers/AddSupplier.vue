
<script setup lang="ts">
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
<template>
  <form @submit.prevent="submit">
    <div class="mb-4">
      <label class="block mb-1 text-sm font-medium">Supplier Name</label>
      <input
        v-model="form.name"
        type="text"
        class="w-full border rounded p-2"
        placeholder="Enter supplier name"
      />
    </div>

    <div class="mb-4">
      <label class="block mb-1 text-sm font-medium">Contact</label>
      <input
        v-model="form.contact"
        type="text"
        class="w-full border rounded p-2"
        placeholder="Enter contact info"
      />
    </div>

    <div class="flex justify-end gap-2">
      <Button type="button" @click="emit('cancel')">Cancel</Button>
      <Button type="submit">Save</Button>
    </div>
  </form>
</template>

