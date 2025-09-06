<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const { props } = usePage()
const plans = props.plans
const subscriptions = props.subscriptions || []   
const success = props.success || null            
const isLoading = ref(false)

// Debug function to check CSRF token
function debugCSRF() {
  console.log('CSRF Token:', props.csrf_token)
  console.log('Page props:', props)
}

// Call debug on component mount
debugCSRF()

// Helper function to handle form submission loading state
function handleSubmit(event) {
  isLoading.value = true
  // Form will submit normally, loading will reset on page change
}

// Helper function to convert days to minutes for display
function convertDaysToMinutes(days) {
  return days * 24 * 60; // days * 24 hours * 60 minutes
}

// Compute most popular plan (middle one or based on some logic)
const popularPlanIndex = computed(() => Math.floor(plans.length / 2))
</script>

<template>
  <Head title="Subscription Plans" />

 <!-- Logout Button -->
    <div class="absolute top-4 left-4 z-50">
      <form method="POST" action="/logout">
        <input type="hidden" name="_token" :value="$page.props.csrf_token" />
        <button 
          type="submit" 
          class="flex items-center px-4 py-2 bg-gray-800 text-white rounded-lg shadow-md hover:bg-gray-900 transition"
        >
          <!-- Left Arrow Icon -->
          <svg xmlns="http://www.w3.org/2000/svg" 
               fill="none" 
               viewBox="0 0 24 24" 
               stroke="currentColor" 
               class="h-5 w-5 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Logout
        </button>
      </form>
    </div>
  
  <!-- Hero Section -->
  <div class="bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 text-white">
    <div class="max-w-6xl mx-auto px-4 py-16 text-center">
      <h1 class="text-4xl md:text-5xl font-bold mb-4">Choose Your Plan</h1>
      <p class="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
        Select the perfect subscription plan for your restaurant management needs
      </p>
    </div>
  </div>

  <section class="max-w-7xl mx-auto py-12 px-4 md:px-8 -mt-8 relative z-10">
    
    <!-- Success Alert -->
    <div v-if="success" class="mb-8 p-4 bg-emerald-50 border border-emerald-200 rounded-xl shadow-sm">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-emerald-800 font-medium">{{ success }}</p>
        </div>
      </div>
    </div>

    <!-- Pricing Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12">
      <div
        v-for="(plan, index) in plans"
        :key="plan.plan_id"
        :class="[
          'relative bg-white rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl hover:-translate-y-1',
          index === popularPlanIndex ? 'ring-2 ring-blue-500 scale-105' : ''
        ]"
      >
        <!-- Popular Badge -->
        <div 
          v-if="index === popularPlanIndex" 
          class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2"
        >
          <span class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-4 py-1 rounded-full text-sm font-semibold shadow-lg">
            Most Popular
          </span>
        </div>

        <div class="p-8">
          <!-- Plan Header -->
          <div class="text-center mb-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ plan.plan_name }}</h3>
            <div class="flex items-baseline justify-center">
              <span class="text-4xl font-extrabold text-gray-900">₱{{ plan.plan_price }}</span>
              <span class="text-gray-500 ml-2">/ {{ plan.plan_duration }} days</span>
            </div>
          </div>

          <!-- Features List -->
          <div class="mb-8">
            <ul class="space-y-3">
              <li class="flex items-center">
                <svg class="h-5 w-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-gray-700">Full Restaurant Management</span>
              </li>
              <li class="flex items-center">
                <svg class="h-5 w-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-gray-700">Inventory Tracking</span>
              </li>
              <li class="flex items-center">
                <svg class="h-5 w-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-gray-700">Order Management</span>
              </li>
              <li class="flex items-center">
                <svg class="h-5 w-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-gray-700">24/7 Support</span>
              </li>
            </ul>
          </div>

          <!-- Subscribe / Free Trial Button -->
          <form 
            method="POST" 
            :action="plan.plan_id == 4 ? route('subscriptions.free-trial') : route('subscriptions.create')" 
            @submit="handleSubmit"
          >
            <input type="hidden" name="_token" :value="$page.props.csrf_token" />
            <input type="hidden" name="plan_id" :value="plan.plan_id" />
            <button
              type="submit"
              :disabled="isLoading"
              :class="[ 
                'w-full py-3 px-6 rounded-xl font-semibold text-white transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-300',
                index === popularPlanIndex 
                  ? 'bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 shadow-lg' 
                  : 'bg-gray-800 hover:bg-gray-900 shadow-md'
              ]"
            >
              <span v-if="!isLoading">
                {{ plan.plan_id == 4 ? 'Start 7 Days Free Trial' : 'Subscribe Now' }}
              </span>
              <span v-else class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
              </span>
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Current Subscriptions Section -->
    <div v-if="subscriptions.length" class="mt-16">
      <div class="bg-white rounded-2xl shadow-xl p-8">
        <div class="flex items-center mb-6">
          <div class="p-2 bg-blue-100 rounded-lg mr-4">
            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <h2 class="text-2xl font-bold text-gray-900">Your Active Subscriptions</h2>
        </div>
        
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="sub in subscriptions"
            :key="sub.userSubscription_id"
            class="p-6 border border-gray-200 rounded-xl bg-gradient-to-br from-gray-50 to-gray-100 hover:shadow-md transition-shadow duration-200"
          >
            <div class="flex items-center justify-between mb-3">
              <span class="text-sm font-medium text-gray-600">Status</span>
              <span 
                :class="[
                  'px-3 py-1 rounded-full text-xs font-semibold',
                  sub.subscription_status === 'Active' 
                    ? 'bg-green-100 text-green-800' 
                    : sub.subscription_status === 'Pending' 
                    ? 'bg-yellow-100 text-yellow-800'
                    : 'bg-red-100 text-red-800'
                ]"
              >
                {{ sub.subscription_status }}
              </span>
            </div>
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium text-gray-600">Expires</span>
              <span class="text-sm font-semibold text-gray-900">{{ sub.subscription_endDate }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-600">Remaining</span>
              <span class="text-sm font-semibold text-blue-600">{{ convertDaysToMinutes(sub.remaining_days) }} minutes</span>
            </div>
          </div>
        </div>
      </div>
    </div>

  </section>
</template>
