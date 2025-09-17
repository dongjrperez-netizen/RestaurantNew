<script setup lang="ts">
import { ref, watch, onBeforeMount } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
  Users, UserRound, Box, ClipboardList,
  UtensilsCrossed, ShoppingCart, Truck,
  Folder, BookOpen, LayoutGrid 
} from "lucide-vue-next"

import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuSub,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSubItem
} from "@/components/ui/sidebar"

import { Collapsible, CollapsibleTrigger, CollapsibleContent } from "@/components/ui/collapsible"
import NavFooter from "@/components/NavFooter.vue"
import NavUser from "@/components/NavUser.vue"

// Navigation items
const navItems = [
  { title: "Reports", href: "/dashboard", icon: LayoutGrid },
  {
    title: "User Management",
    icon: Users,
    children: [
      { title: "Employees", href: '/employees', icon: UserRound },
      { title: "Suppliers", href: "/suppliers", icon: Truck },
    ]
  },
  {
    title: "Menu Management",
    icon: UtensilsCrossed,
    children: [
      { title: "Menu Items", href: "/menu", icon: ClipboardList },
      { title: "Categories", href: "/menu-categories", icon: Folder },
      { title: "Analytics", href: "/menu-analytics", icon: LayoutGrid },
    ]
  },
  {
    title: "POS Management",
    icon: ShoppingCart,
    children: [
      { title: "Orders", href: "/orders", icon: ClipboardList },
      { title: "Kitchen Display", href: "/orders/kitchen", icon: UtensilsCrossed },
      { title: "Order Analytics", href: "/orders/analytics", icon: LayoutGrid },
    ]
  },
  {
    title: "Purchase Management",
    icon: Truck,
    children: [
      { title: "Purchase Orders", href: "/purchase-orders", icon: ClipboardList },
      { title: "Bills", href: "/bills", icon: Truck },
    ]
  },
    {
    title: "Inventory",
    icon: Truck,
    children: [
      { title: "Stock-List", href: "/stock-list", icon: Users },
    ]
  },
]

const footerNavItems = [
  { title: "Github Repo", href: "https://github.com/laravel/vue-starter-kit", icon: Folder },
  { title: "Documentation", href: "https://laravel.com/docs/starter-kits#vue", icon: BookOpen },
]

// Controlled state for collapsibles
const openCollapsibles = ref<Record<string, boolean>>({})

// Initialize collapsibles
navItems.forEach(item => {
  if (item.children) openCollapsibles.value[item.title] = false
})

// Persist collapsible state across navigation
onBeforeMount(() => {
  const saved = sessionStorage.getItem('sidebarState')
  if (saved) openCollapsibles.value = JSON.parse(saved)
})

watch(openCollapsibles, (val) => {
  sessionStorage.setItem('sidebarState', JSON.stringify(val))
}, { deep: true })

const handleNavigation = (href: string) => {
  console.log('Navigating to:', href)
  // Force full browser navigation for all sidebar links to avoid Inertia issues
  console.log('Using window.location for:', href)
  window.location.href = href
}
</script>

<template>
  <Sidebar class="flex flex-col h-full">
    <SidebarContent class="flex-1">
      <SidebarGroup>
        <SidebarGroupLabel>Restaurant</SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <template v-for="item in navItems" :key="item.title">
              
              <!-- Single link -->
              <SidebarMenuItem v-if="item.href">
                <SidebarMenuButton @click="handleNavigation(item.href)">
                  <component :is="item.icon" class="mr-2 h-4 w-4"/>
                  <span>{{ item.title }}</span>
                </SidebarMenuButton>
              </SidebarMenuItem>

              <!-- Collapsible group -->
              <Collapsible
                v-else
                v-model:open="openCollapsibles[item.title]"
              >
                <SidebarMenuItem>
                  <CollapsibleTrigger asChild>
                    <SidebarMenuButton>
                      <component :is="item.icon" class="mr-2 h-4 w-4" />
                      <span>{{ item.title }}</span>
                    </SidebarMenuButton>
                  </CollapsibleTrigger>

                  <CollapsibleContent>
                    <SidebarMenuSub>
                      <SidebarMenuSubItem v-for="child in item.children" :key="child.title">
                        <SidebarMenuButton @click="handleNavigation(child.href)">
                          <component :is="child.icon" class="mr-2 h-4 w-4" />
                          <span>{{ child.title }}</span>
                        </SidebarMenuButton>
                      </SidebarMenuSubItem>
                    </SidebarMenuSub>
                  </CollapsibleContent>
                </SidebarMenuItem>
              </Collapsible>

            </template>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>
    </SidebarContent>

    <!-- Footer -->
    <SidebarFooter class="mt-auto border-t">
      <NavFooter :items="footerNavItems"/>
      <NavUser></NavUser>
    </SidebarFooter>
  </Sidebar>
</template>
