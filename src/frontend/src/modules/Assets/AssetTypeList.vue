<template>
  <div>
    <add-asset-type :addNewAssetType="addNewAssetType" />
    <widget
      :title="$tc('phrases.applianceType', 1)"
      :subscriber="subscriber"
      :route_name="'/assets'"
      color="green"
      :reset-key="resetKey"
    >
      <md-table>
        <md-table-row>
          <md-table-head v-for="(item, index) in headers" :key="index">
            {{ item }}
          </md-table-head>
        </md-table-row>

        <md-table-row
          v-for="(assetType, index) in assetTypeService.list"
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
                  v-model="assetType.name"
                  v-validate="'required|min:5'"
                ></md-input>
                <span class="md-error">
                  {{ errors.first("Appliance Name") }}
                </span>
              </md-field>
            </div>
            <div class="md-layout-item" v-else>{{ assetType.name }}&nbsp;</div>
          </md-table-cell>

          <md-table-cell>{{ assetType.updatedAt }}</md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import AddAssetType from "./AddAssetType"
import { EventBus } from "@/shared/eventbus"
import { AssetTypeService } from "@/services/AssetTypeService"
import { notify } from "@/mixins/notify"

export default {
  name: "AssetTypeList",
  mixins: [notify],
  components: { Widget, AddAssetType },

  data() {
    return {
      addNewAssetType: false,
      subscriber: "assetTypeList",
      assetTypeService: new AssetTypeService(),
      headers: [this.$tc("words.name"), this.$tc("phrases.lastUpdate")],
      resetKey: 0,
      loading: false,
      updateAppliance: null,
      currency: this.$store.getters["settings/getMainSettings"].currency,
    }
  },
  mounted() {
    this.getAssetTypes()
    EventBus.$on("AssetTypeAdded", () => {
      this.addNewAssetType = false
      this.getAssetTypes()
    })
    EventBus.$on("addAssetTypeClosed", () => {
      this.addNewAssetType = false
      this.getAssetTypes()
    })
  },
  methods: {
    showAddAssetType() {
      this.addNewAssetType = true
    },

    addToList(assetType) {
      let assetTypeItem = {
        id: assetType.id,
        name: assetType.name,
        edit: false,
      }
      this.assetTypeService.list.push(assetTypeItem)
    },
    async getAssetTypes() {
      await this.assetTypeService.getAssetsTypes()
      this.loading = false
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.assetTypeService.list.length,
      )
    },
    async updateAssetType(assetType) {
      let validator = await this.$validator.validateAll()
      if (!validator) {
        return
      }
      this.loading = true
      this.$swal({
        type: "question",
        title: "Update Appliance Type",
        text: "Are you sure to update the asset type ?",
        showCancelButton: true,
        cancelButtonText: this.$tc("words.cancel"),
        confirmButtonText: this.$tc("words.update"),
      }).then(async (response) => {
        if (response.value) {
          this.updateAppliance = false
          try {
            await this.assetTypeService.updateAssetType(assetType)
            this.alertNotify("success", "Appliance Type Updated Successfully.")
            this.resetKey++
          } catch (e) {
            this.alertNotify("error", e.message)
          }
        }
      })
      this.loading = false
    },
    async deleteAssetType(assetType) {
      this.$swal({
        type: "question",
        title: this.$tc("phrases.deleteAssetType", 0),
        text: this.$tc("phrases.deleteAssetType", 2),
        showCancelButton: true,
        cancelButtonText: this.$tc("words.cancel"),
        confirmButtonText: this.$tc("words.delete"),
      }).then(async (response) => {
        if (response.value) {
          try {
            this.loading = true
            await this.assetTypeService.deleteAssetType(assetType)
            this.loading = false
            this.alertNotify("success", this.$tc("phrases.deleteAssetType", 1))
            await this.getAssetTypes()
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
      this.addNewAssetType = data
    },
  },
}
</script>

<style scoped></style>
