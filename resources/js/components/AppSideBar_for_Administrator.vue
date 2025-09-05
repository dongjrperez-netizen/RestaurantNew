<script setup lang="ts">

import { Sidebar, SidebarContent} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import {LayoutGrid,ClipboardList ,    } from 'lucide-vue-next';
import NavAdministrator from './NavAdministrator.vue';




const administrator_side: NavItem[]  = [

    {
        title: 'Administrator',
        href: '/administrator',
        icon: LayoutGrid ,
    },
   {
        title: 'Request',
        href: '/request',
        icon: ClipboardList,
    },
];




</script>

<template>
    <Sidebar>

        <SidebarContent>
            <NavAdministrator :items="administrator_side" />
        </SidebarContent>
       
    </Sidebar>
    <slot />
</template>