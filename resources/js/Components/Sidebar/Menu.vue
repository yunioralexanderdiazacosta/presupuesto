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
    <div class="menu-item">
        <!--begin:Menu link-->
        <Link class="menu-link" :href="route(item.link)">
            <span class="menu-icon">
                <span class="svg-icon svg-icon-2" v-html="item.icon"></span>
            </span>
            <span class="menu-title">{{item.title}}</span>
        </Link>
        <!--end:Menu link-->
    </div>
</template>