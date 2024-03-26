<script setup>
</script>
<template>
	<div class="card mb-4" :style="{backgroundPosition: 'right top', height:'60px', backgroundSize: '5% auto', backgroundImage: 'url('+path+'/assets/media/svg/shapes/abstract-4.svg', backgroundRepeat: 'no-repeat' }">
        <!--begin::Card header-->
        <div class="card-header border-0">
            <!--begin::Card Title-->
            <div class="card-title m-0 p-3">
                <!--begin::Name-->
                <div class="fs-3 fw-bold text-dark ">{{$page.props.budget.name}}</div>
                <!--end::Name-->
            </div>
            <!--end::Card Title-->
        </div>
        <!--end:: Card header-->
    </div>
</template>