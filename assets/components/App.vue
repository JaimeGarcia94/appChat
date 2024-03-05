<template>
   <div class="container py-5 px-4">
 

  <div class="row rounded-lg overflow-hidden shadow">
    <!-- Users box-->
    <left></left>
    <!-- Chat Box-->
    <!-- <right></right> -->
    <router-view :key="$route.fullPath"></router-view>
  </div>
</div>

    
</template>

<script>
import Left from './Left/Left.vue'
import Right from './Right/Right.vue'
export default {
  components : {Left, Right}
}
</script>