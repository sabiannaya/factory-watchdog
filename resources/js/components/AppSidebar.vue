<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Clock, BarChart2, Layers, Tool } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />

            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Data Management</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Production'">
                            <Link href="/data-management/production">
                                <LayoutGrid />
                                <span>Production</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Machine'">
                            <Link href="/data-management/machine">
                                <Folder />
                                <span>Machine</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Target'">
                            <Link href="/data-management/target">
                                <BookOpen />
                                <span>Target</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Products (Setting Produk)'">
                            <Link href="/data-management/products">
                                <Folder />
                                <span>Products</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Glue Spreaders'">
                            <Link href="/data-management/glue-spreaders">
                                <Tool />
                                <span>Glue Spreaders</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>


                    <!-- kept core data management links here; summaries/logs moved to dedicated group -->
                </SidebarMenu>
            </SidebarGroup>

            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Summaries</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Group Summary (aggregates)'"><!-- moved -->
                            <Link href="/data-management/aggregates/machine-groups">
                                <BarChart2 />
                                <span>Group Summary</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Production Summary (aggregates)'"><!-- moved -->
                            <Link href="/data-management/aggregates/productions">
                                <Layers />
                                <span>Production Summary</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                </SidebarMenu>
            </SidebarGroup>

            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Logs</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Hourly Logs'">
                            <Link href="/data-management/hourly-logs">
                                <Clock />
                                <span>Hourly Logs</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>


                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Group Logs (hourly aggregated)'"><!-- moved -->
                            <Link href="/data-management/logs/group">
                                <BarChart2 />
                                <span>Group Logs</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Production Logs (hourly aggregated)'"><!-- moved -->
                            <Link href="/data-management/logs/production">
                                <Layers />
                                <span>Production Logs</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
