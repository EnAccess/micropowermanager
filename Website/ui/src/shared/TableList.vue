<template>
  <table
    id="dt_basic"
    class="table table-striped table-bordered table-hover dataTable no-footer table-responsive"
    width="100%"
    role="grid"
    aria-describedby="dt_basic_info"
    style="width: 100%"
  >
    <slot name="header"></slot>

    <slot name="body"></slot>
  </table>
</template>

<script>
export default {
  name: "TableList",
}
</script>

<style scoped>
tbody > tr {
  cursor: pointer;
}
</style>
