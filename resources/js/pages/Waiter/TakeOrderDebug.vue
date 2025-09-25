<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import WaiterLayout from '@/layouts/WaiterLayout.vue';

interface Props {
  tables: any[];
  employee: any;
}

const props = defineProps<Props>();

console.log('TakeOrderDebug mounted');
console.log('Employee:', props.employee);
console.log('Tables:', props.tables);
console.log('Tables count:', props.tables?.length || 0);
</script>

<template>
  <Head title="Take Order Debug" />

  <WaiterLayout :employee="employee">
    <template #title>Take Order Debug</template>

    <div style="padding: 20px; background: white;">
      <h1 style="color: black; font-size: 24px; margin-bottom: 20px;">Debug Information</h1>
      
      <div style="background: #f0f0f0; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
        <strong>Employee:</strong>
        <pre style="margin: 5px 0;">{{ JSON.stringify(employee, null, 2) }}</pre>
      </div>
      
      <div style="background: #f0f0f0; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
        <strong>Tables Count:</strong> {{ tables?.length || 0 }}
      </div>
      
      <div style="background: #f0f0f0; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
        <strong>Tables Data:</strong>
        <pre style="margin: 5px 0;">{{ JSON.stringify(tables, null, 2) }}</pre>
      </div>
    </div>
  </WaiterLayout>
</template>
