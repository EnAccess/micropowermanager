<template>
  <widget
    :hidden="!showAdd"
    :id="'new-connection-group'"
    :title="$tc('phrases.newConnectionGroup')"
    :color="'red'"
  >
    <md-card>
      <md-card-content>
        <form ref="connectionGroupForm">
          <div class="md-layout md-gutter">
            <div class="md-layout-item">
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('words.name')),
                }"
              >
                <label for="name">
                  {{ $tc("words.name") }}
                </label>
                <md-input
                  id="name"
                  :name="$tc('words.name')"
                  v-model="connectionGroupService.connectionGroup.name"
                  v-validate="'required|min:3'"
                />
                <span class="md-error">
                  {{ errors.first($tc("words.name")) }}
                </span>
              </md-field>
            </div>
          </div>
        </form>
      </md-card-content>

      <md-card-actions>
        <md-button role="button" class="md-raised md-primary" @click="store">
          {{ $tc("words.save") }}
        </md-button>
        <md-button role="button" class="md-raised" @click="hide">
          {{ $tc("words.close") }}
        </md-button>
      </md-card-actions>
    </md-card>
  </widget>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { EventBus } from "@/shared/eventbus"
import { ConnectionGroupService } from "@/services/ConnectionGroupService"
import { notify } from "@/mixins/notify"

export default {
  name: "NewConnectionGroup",
  mixins: [notify],
  components: { Widget },
  data() {
    return {
      connectionGroupService: new ConnectionGroupService(),
      showAdd: false,
    }
  },
  created() {},
  mounted() {
    EventBus.$on("showNewConnectionGroup", this.show)
  },
  methods: {
    async store() {
      let validator = await this.$validator.validateAll()
      if (!validator) {
        return
      }
      this.hide()
      try {
        await this.connectionGroupService.createConnectionGroup()
        this.alertNotify("success", this.$tc("phrases.newConnectionGroup", 2))
        this.$refs["connectionGroupForm"].reset()
        EventBus.$emit("connectionGroupAdded", this.connectionGroup)
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    hide() {
      this.showAdd = false
    },
    show() {
      this.showAdd = true
    },
  },
  watch: {
    showAdd(value) {
      if (value) {
        this.errors.clear()
      }
    },
  },
}
</script>

<style scoped>
.full-width {
  width: 100% !important;
}
</style>
