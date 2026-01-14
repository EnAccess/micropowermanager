<template>
  <div>
    <add-appliance-type :addNewApplianceType="addNewApplianceType" />
    <widget
      :title="$tc('phrases.applianceType', 1)"
      :subscriber="subscriber"
      :route_name="'/appliances'"
      color="primary"
      :reset-key="resetKey"
    >
      <md-table>
        <md-table-row>
          <md-table-head v-for="(item, index) in headers" :key="index">
            {{ item }}
          </md-table-head>
        </md-table-row>

        <md-table-row
          v-for="(applianceType, index) in applianceTypeService.list"
          :key="index"
        >
          <md-table-cell>
            <div class="md-layout" v-if="updateAppliance === index">
              <md-field
                :class="{
                  'md-invalid': errors.has('Appliance Name'),
                }"
              >
                <label for="applianceName"></label>
                <md-input
                  name="Appliance Name"
                  type="text"
                  v-model="applianceType.name"
                  v-validate="'required|min:5'"
                ></md-input>
                <span class="md-error">
                  {{ errors.first("Appliance Name") }}
                </span>
              </md-field>
            </div>
            <div class="md-layout-item" v-else>
              {{ applianceType.name }}&nbsp;
            </div>
          </md-table-cell>

          <md-table-cell>
            <md-icon
              :class="applianceType.paygoEnabled ? 'md-primary' : 'md-accent'"
            >
              {{ applianceType.paygoEnabled ? "check" : "close" }}
            </md-icon>
          </md-table-cell>
          <md-table-cell>{{ applianceType.updatedAt }}</md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import AddApplianceType from "./AddApplianceType"
import { EventBus } from "@/shared/eventbus"
import { ApplianceTypeService } from "@/services/ApplianceTypeService"
import { notify } from "@/mixins/notify"

export default {
  name: "ApplianceTypeList",
  mixins: [notify],
  components: { Widget, AddApplianceType },

  data() {
    return {
      addNewApplianceType: false,
      subscriber: "applianceTypeList",
      applianceTypeService: new ApplianceTypeService(),
      headers: [
        this.$tc("words.name"),
        this.$tc("phrases.paygoEnabled"),
        this.$tc("phrases.lastUpdate"),
      ],
      resetKey: 0,
      loading: false,
      updateAppliance: null,
      currency: this.$store.getters["settings/getMainSettings"].currency,
    }
  },
  mounted() {
    this.getApplianceTypes()
    EventBus.$on("ApplianceTypeAdded", () => {
      this.addNewApplianceType = false
      this.getApplianceTypes()
    })
    EventBus.$on("addApplianceTypeClosed", () => {
      this.addNewApplianceType = false
      this.getApplianceTypes()
    })
  },
  methods: {
    showAddApplianceType() {
      this.addNewApplianceType = true
    },

    addToList(applianceType) {
      let applianceTypeItem = {
        id: applianceType.id,
        name: applianceType.name,
        edit: false,
      }
      this.applianceTypeService.list.push(applianceTypeItem)
    },
    async getApplianceTypes() {
      await this.applianceTypeService.getAppliancesTypes()
      this.loading = false
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.applianceTypeService.list.length,
      )
    },
    async updateApplianceType(applianceType) {
      let validator = await this.$validator.validateAll()
      if (!validator) {
        return
      }
      this.loading = true
      this.$swal({
        type: "question",
        title: "Update Appliance Type",
        text: "Are you sure to update the appliance type ?",
        showCancelButton: true,
        cancelButtonText: this.$tc("words.cancel"),
        confirmButtonText: this.$tc("words.update"),
      }).then(async (response) => {
        if (response.value) {
          this.updateAppliance = false
          try {
            await this.applianceTypeService.updateApplianceType(applianceType)
            this.alertNotify("success", "Appliance Type Updated Successfully.")
            this.resetKey++
          } catch (e) {
            this.alertNotify("error", e.message)
          }
        }
      })
      this.loading = false
    },
    async deleteApplianceType(applianceType) {
      this.$swal({
        type: "question",
        title: this.$tc("phrases.deleteApplianceType", 0),
        text: this.$tc("phrases.deleteApplianceType", 2),
        showCancelButton: true,
        cancelButtonText: this.$tc("words.cancel"),
        confirmButtonText: this.$tc("words.delete"),
      }).then(async (response) => {
        if (response.value) {
          try {
            this.loading = true
            await this.applianceTypeService.deleteApplianceType(applianceType)
            this.loading = false
            this.alertNotify(
              "success",
              this.$tc("phrases.deleteApplianceType", 1),
            )
            await this.getApplianceTypes()
            this.resetKey++
          } catch (e) {
            this.loading = false
            this.alertNotify("error", e.message)
          }
        }
      })
    },
    openApplianceUpdate(index) {
      if (this.updateAppliance === index) {
        this.updateAppliance = null
      } else {
        this.updateAppliance = index
      }
    },
    closeApplianceUpdate() {
      this.updateAppliance = null
    },
    closeAddComponent(data) {
      this.addNewApplianceType = data
    },
  },
}
</script>

<style scoped></style>
