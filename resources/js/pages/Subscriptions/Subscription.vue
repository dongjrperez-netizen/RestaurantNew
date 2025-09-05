<script setup>
import { Head, usePage } from '@inertiajs/vue3'

const { props } = usePage()
const plans = props.plans
const subscriptions = props.subscriptions || []   
const success = props.success || null            

// Debug function to check CSRF token
function debugCSRF() {
  console.log('CSRF Token:', props.csrf_token)
  console.log('Page props:', props)
}

// Call debug on component mount
debugCSRF()
</script>

<template>
  <section class="max-w-7xl mx-auto py-12 px-4 md:px-8">
    
    <!-- success alert -->
    <div v-if="success" class="bg-green-200 text-green-800 p-3 rounded mb-6">
      {{ success }}
    </div>

    <!-- 3-column grid, responsive down to 1 column -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="plan in plans"
        :key="plan.plan_id"
        class="p-6 border rounded shadow bg-white flex flex-col"
      >
        <h2 class="text-2xl font-bold mb-2">{{ plan.plan_name }}</h2>
        <p class="text-xl font-semibold mb-2">
          ₱{{ plan.plan_price }} / {{ plan.plan_duration }} days
        </p>

        <!-- Debug plan info -->
        <div class="text-xs text-gray-500 mb-2">
          Plan ID: {{ plan.plan_id }} | PayPal Plan ID:
          {{ plan.paypal_plan_id || 'Missing' }}
        </div>

        <!-- Simple form with CSRF token -->
        <form method="POST" :action="route('subscriptions.create')" class="mt-auto">
          <input type="hidden" name="_token" :value="$page.props.csrf_token" />
          <input type="hidden" name="plan_id" :value="plan.plan_id" />
          <button
            type="submit"
            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 w-full"
          >
            Subscribe Now
          </button>
        </form>
      </div>
    </div>

    <!-- show user subscriptions -->
    <div v-if="subscriptions.length" class="mt-12">
      <h2 class="text-2xl font-bold mb-4">Your Subscriptions</h2>
      <ul class="space-y-2">
        <li
          v-for="sub in subscriptions"
          :key="sub.userSubscription_id"
          class="border p-3 rounded bg-gray-50"
        >
          Status: {{ sub.subscription_status }} <br />
          Ends: {{ sub.subscription_endDate }}
        </li>
      </ul>
    </div>

  </section>
</template>
