<template>
  <div>
    <NewConnectionGroup />
    <widget
      :id="'connection-Groups-list'"
      :title="$tc('phrases.connectionGroup', 2)"
      :paginator="connectionGroups.paginator"
      :subscriber="subscriber"
      :button="true"
      :button-text="$tc('phrases.newConnectionGroup')"
      @widgetAction="addNew"
      :color="'green'"
    >
      <md-table md-card style="margin-left: 0">
        <md-table-row>
          <md-table-head>#</md-table-head>
          <md-table-head>{{ $tc("words.id") }}</md-table-head>
          <md-table-head>{{ $tc("words.name") }}</md-table-head>
          <md-table-head></md-table-head>
        </md-table-row>
        <md-table-row
          v-for="(Group, index) in connectionGroups"
          :key="Group.id"
        >
          <md-table-cell>{{ index + 1 }}</md-table-cell>
          <md-table-cell>{{ Group.id }}</md-table-cell>
          <md-table-cell>
            <div v-if="editConnectionGroup === Group.id">
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('words.name')),
                }"
              >
                <label for="ConnectionGroup">
                  {{ $tc("phrases.editConnectionGroup", 0) }}
                </label>
                <md-input
                  id="ConnectionGroup"
                  :name="$tc('words.name')"
                  v-model="Group.name"
                  v-validate="'required|min:3'"
                />
                <span class="md-error">
                  {{ errors.first($tc("words.name")) }}
                </span>
              </md-field>
            </div>
            <div v-else>
              {{ Group.name }}
            </div>
          </md-table-cell>
          <md-table-cell>
            <div v-if="editConnectionGroup === Group.id">
              <md-button
                class="md-icon-button"
                @click="updateConnectionGroup(Group)"
              >
                <md-icon>save</md-icon>
              </md-button>
              <md-button
                class="md-icon-button"
                @click="editConnectionGroup = null"
              >
                <md-icon>close</md-icon>
              </md-button>
            </div>
            <div v-else>
              <md-button
                class="md-icon-button"
                @click="editConnectionGroup = Group.id"
              >
                <md-icon>edit</md-icon>
              </md-button>
            </div>
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { EventBus } from "@/shared/eventbus"
import { ConnectionGroupService } from "@/services/ConnectionGroupService"
import NewConnectionGroup from "./NewConnectionGroup"
import { notify } from "@/mixins/notify"
export default {
  name: "ConnectionGroupsList",
  mixins: [notify],
  components: { Widget, NewConnectionGroup },
  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
    EventBus.$on("searching", this.searching)
    EventBus.$on("end_searching", this.endSearching)
    EventBus.$on("connectionGroupAdded", this.getConnectionGroups)

    this.getConnectionGroups()
  },

  data() {
    return {
      connectionGroupService: new ConnectionGroupService(),
      subscriber: "connection-Groups-list",
      connectionGroups: [],
      editConnectionGroup: null,
    }
  },
  methods: {
    checkConfirm(result) {
      return "value" in result
    },
    async updateConnectionGroup(connectionGroup) {
      let validator = await this.$validator.validateAll()
      if (!validator) {
        return
      }
      this.$swal({
        type: "question",
        title: this.$tc("phrases.editConnectionGroup", 0),
        text: this.$tc("phrases.editConnectionGroup", 1),
        showCancelButton: true,
        cancelButtonText: this.$tc("words.no"),
        confirmButtonText: this.$tc("words.yes"),
      }).then((response) => {
        if (this.checkConfirm(response)) {
          try {
            this.connectionGroupService.updateConnectionGroup(connectionGroup)
            this.editConnectionGroup = null
            this.alertNotify(
              "success",
              this.$tc("phrases.editConnectionGroup", 2),
            )
          } catch (e) {
            this.alertNotify("error", e)
          }
        } else {
          return
        }
      })
    },
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.connectionGroups = this.connectionGroupService.updateList(data)
    },
    async getConnectionGroups() {
      try {
        this.connectionGroups =
          await this.connectionGroupService.getConnectionGroups()
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          this.connectionGroups.length,
        )
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    addNew() {
      EventBus.$emit("showNewConnectionGroup")
    },
  },
}
</script>

<style scoped></style>
