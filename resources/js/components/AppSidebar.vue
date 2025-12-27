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
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Clock, BarChart2, Layers, Wrench, PlusCircle, ClipboardList, Users, Shield, Calendar } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed } from 'vue';

const page = usePage();

const isSuper = computed(() => {
    return (page.props.auth as any)?.user?.is_super === true;
});

const canAccessGlueSpreaders = computed(() => {
    const user = (page.props.auth as any)?.user;
    return isSuper.value || user?.can_access_glue_spreaders === true;
});

const canAccessWarehouse = computed(() => {
    const user = (page.props.auth as any)?.user;
    return isSuper.value || user?.can_access_warehouse === true;
});

const mainNavItems = computed<NavItem[]>(() => {
    if (!isSuper.value) return [];
    return [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];
});

const homeLink = computed(() => isSuper.value ? dashboard() : '/input');

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="homeLink">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />

            <!-- Input Section -->
            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Input</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Record Hourly Input'">
                            <Link href="/input">
                                <PlusCircle />
                                <span>Hourly Input</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <!-- Data Management Section -->
            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Data Management</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Productions'">
                            <Link href="/data-management/production">
                                <LayoutGrid />
                                <span>Productions</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Machines'">
                            <Link href="/data-management/machine">
                                <Folder />
                                <span>Machines</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Daily Targets'">
                            <Link href="/data-management/targets">
                                <ClipboardList />
                                <span>Targets</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Products'">
                            <Link href="/data-management/products">
                                <Folder />
                                <span>Products</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem v-if="canAccessGlueSpreaders">
                        <SidebarMenuButton as-child :tooltip="'Glue Spreaders'">
                            <Link href="/data-management/glue-spreaders">
                                <Wrench />
                                <span>Glue Spreaders</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem v-if="canAccessWarehouse">
                        <SidebarMenuButton as-child :tooltip="'Warehouse'">
                            <Link href="/data-management/warehouses">
                                <Wrench />
                                <span>Warehouse</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <!-- Logs Section -->
            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Logs</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'All Hourly Logs'">
                            <Link href="/logs">
                                <Clock />
                                <span>Hourly Logs</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Logs by Machine Group'">
                            <Link href="/logs/group">
                                <BarChart2 />
                                <span>Group Logs</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Logs by Production'">
                            <Link href="/logs/production">
                                <Layers />
                                <span>Production Logs</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    
                </SidebarMenu>
            </SidebarGroup>

            <!-- Summary Section -->
            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Summary</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Machine Group Summary'">
                            <Link href="/summary/machine-groups">
                                <BarChart2 />
                                <span>Machine Groups</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Production Summary'">
                            <Link href="/summary/productions">
                                <Layers />
                                <span>Productions</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Daily Summary'">
                            <Link href="/summary/daily">
                                <Calendar />
                                <span>Daily Summary</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <!-- Admin Section (Super users only) -->
            <SidebarGroup v-if="isSuper" class="px-2 py-0">
                <SidebarGroupLabel class="text-purple-600 dark:text-purple-400">
                    <Shield class="size-3 mr-1" />
                    Admin
                </SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'User Management'">
                            <Link href="/admin/users">
                                <Users />
                                <span>Users</span>
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
