<template>
  <div>
    <widget color="primary" title="{{ PluginName }} Overview">
      <div class="md-layout md-gutter">
        <div
          class="md-layout-item md-small-size-100 md-xsmall-size-100 md-medium-size-100 md-size-100"
        >
          <Credential />
        </div>
      </div>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue";

export default {
  name: "Overview",
  components: { Widget },
  data() {
    return {};
  },
  mounted() {},
  methods: {},
};
</script>

<style scoped></style>
