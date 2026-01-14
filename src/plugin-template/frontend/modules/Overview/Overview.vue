<template>
  <div>
    <widget color="primary" title="{{Plugin-Name}} Overview">
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-size-100">
          <md-empty-state
            md-label="{{Plugin-Name}} MicroPowerManager plugin "
            md-description="Your plugin was created successfully 🎉. Replace this page with your own implementation and extend the platform with custom functionality."
          >
            <md-button
              class="md-primary md-raised"
              href="https://micropowermanager.io/development/plugins.html"
              target="_blank"
              rel="noopener"
            >
              Plugin Development Guide
            </md-button>
          </md-empty-state>
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
