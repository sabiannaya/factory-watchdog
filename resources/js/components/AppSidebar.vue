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
import { useLocalization } from '@/composables/useLocalization';

const { t } = useLocalization();

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
            title: t('app.dashboard'),
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
                <SidebarGroupLabel>{{ t('nav.input') }}</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="t('nav.hourly_input')">
                            <Link href="/input">
                                <PlusCircle />
                                <span>{{ t('nav.hourly_input') }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <!-- Data Management Section -->
            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>{{ t('nav.data_management') }}</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="t('nav.productions')">
                            <Link href="/data-management/production">
                                <LayoutGrid />
                                <span>{{ t('nav.productions') }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="t('nav.machine_groups')">
                            <Link href="/data-management/machine">
                                <Folder />
                                <span>{{ t('nav.machine_groups') }}</span>
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
                        <SidebarMenuButton as-child :tooltip="t('nav.products')">
                            <Link href="/data-management/products">
                                <Folder />
                                <span>{{ t('nav.products') }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem v-if="canAccessGlueSpreaders">
                        <SidebarMenuButton as-child :tooltip="t('nav.glue_spreaders')">
                            <Link href="/data-management/glue-spreaders">
                                <Wrench />
                                <span>{{ t('nav.glue_spreaders') }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem v-if="canAccessWarehouse">
                        <SidebarMenuButton as-child :tooltip="t('nav.warehouses')">
                            <Link href="/data-management/warehouses">
                                <Wrench />
                                <span>{{ t('nav.warehouses') }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <!-- Logs Section -->
            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>{{ t('nav.logs') }}</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="t('nav.hourly_logs')">
                            <Link href="/logs">
                                <Clock />
                                <span>{{ t('nav.hourly_logs') }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="'Group Logs'">
                            <Link href="/logs/group">
                                <BarChart2 />
                                <span>Group Logs</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="t('nav.production_logs')">
                            <Link href="/logs/production">
                                <Layers />
                                <span>{{ t('nav.production_logs') }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    
                </SidebarMenu>
            </SidebarGroup>

            <!-- Summary Section -->
            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>{{ t('nav.summary') }}</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="t('nav.machine_groups')">
                            <Link href="/summary/machine-groups">
                                <BarChart2 />
                                <span>{{ t('nav.machine_groups') }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="t('nav.productions')">
                            <Link href="/summary/productions">
                                <Layers />
                                <span>{{ t('nav.productions') }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="t('nav.daily_summary')">
                            <Link href="/summary/daily">
                                <Calendar />
                                <span>{{ t('nav.daily_summary') }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <!-- Admin Section (Super users only) -->
            <SidebarGroup v-if="isSuper" class="px-2 py-0">
                <SidebarGroupLabel class="text-purple-600 dark:text-purple-400">
                    <Shield class="size-3 mr-1" />
                    {{ t('nav.admin') }}
                </SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :tooltip="t('nav.users')">
                            <Link href="/admin/users">
                                <Users />
                                <span>{{ t('nav.users') }}</span>
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
