<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    item: Object,
    index: Number
})

var active = ref(0);

const change = (position) => {
    if(active.value == position){
        active.value = 0;
    }else{
        active.value = position;
    }
}
</script>
<template>
    <div class="menu-item" v-if="item.subitems.length == 0" v-role:any="item.role">
        <!--begin:Menu link-->
        <Link class="menu-link" :href="route(item.link)">
            <span class="menu-icon">
                <span class="svg-icon svg-icon-2" v-html="item.icon"></span>
            </span>
            <span class="menu-title">{{item.title}}</span>
        </Link>
        <!--end:Menu link-->
    </div>

    <div class="menu-item menu-accordion" v-else @click="change(index)" :class="{'hover show': active == index}" v-role:any="item.role">
        <!--begin:Menu link-->
        <span class="menu-link">
            <span class="menu-icon">
                 <span class="svg-icon svg-icon-2" v-html="item.icon"></span>
            </span>
            <span class="menu-title">{{item.title}}</span>
            <span class="menu-arrow"></span>
        </span>
        <!--end:Menu link-->
        <!--begin:Menu sub-->
        <div class="menu-sub menu-sub-accordion" v-for="(subitem, i) in item.subitems" :key="i">
            <!--begin:Menu item-->
            <div class="menu-item" v-if="subitem.subitems.length == 0">
                <!--begin:Menu link-->
                <Link class="menu-link" :href="route(subitem.link)">
                    <span class="menu-bullet">
                        <span class="bullet bullet-dot"></span>
                    </span>
                    <span class="menu-title">{{subitem.title}}</span>
                </Link>
                <!--end:Menu link-->
            </div>
            <!--end:Menu item-->

            <!--begin:Menu sub-->
            <div class="menu-sub menu-sub-accordion" v-else>
                <!--begin:Menu item-->
                <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
                    <!--begin:Menu link-->
                    <span class="menu-link">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">{{subitem.title}}</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <!--end:Menu link-->
                    <!--begin:Menu sub-->
                    <div class="menu-sub menu-sub-accordion" v-for="(subitemj, j) in subitem.subitems" :key="j">
                        <!--begin:Menu item-->
                        <div class="menu-item">
                            <!--begin:Menu link-->
                            <Link class="menu-link" :href="route(subitemj.link)">
                                <span class="menu-bullet">
                                    <span class="bullet bullet-dot"></span>
                                </span>
                                <span class="menu-title">{{subitemj.title}}</span>
                            </Link>
                            <!--end:Menu link-->
                        </div>
                        <!--end:Menu item-->
                    </div>
                    <!--end:Menu sub-->
                </div>
                <!--end:Menu item-->
            </div>
            <!--end:Menu sub-->
        </div>
    </div>
</template>