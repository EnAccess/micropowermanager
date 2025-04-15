<template>
  <div class="md-layout md-gutter">
    <div class="md-layout-item md-size-50 md-small-size-50">
      <asset-type-list />
    </div>
    <div class="md-layout-item md-small-size-100 md-size-50">
      <add-asset :addNewAsset="addNewAsset" />
      <widget
        :title="$tc('menu.Appliances')"
        :subscriber="subscriber"
        :route_name="'/assets'"
        :button="true"
        :button-text="$tc('phrases.newAppliance', 1)"
        @widgetAction="showAddAsset"
        :reset-key="resetKey"
        :paginator="applianceService.paginator"
      >
        <md-table>
          <md-table-row>
            <md-table-head v-for="(item, index) in headers" :key="index">
              {{ item }}
            </md-table-head>
          </md-table-row>

          <md-table-row
            v-for="(asset, index) in applianceService.list"
            :key="index"
          >
            <md-table-cell>
              <div class="md-layout-item">{{ asset.assetTypeName }}&nbsp;</div>
            </md-table-cell>

            <md-table-cell>
              <div class="md-layout" v-if="updatingAppliance === index">
                <md-field
                  :class="{
                    'md-invalid': errors.has('Appliance Name'),
                  }"
                >
                  <label for="applianceName"></label>
                  <md-input
                    name="applianceName"
                    type="text"
                    v-model="asset.name"
                    v-validate="'required|min:5'"
                  ></md-input>
                  <span class="md-error">
                    {{ errors.first("Appliance Name") }}
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item" v-else>{{ asset.name }}&nbsp;</div>
            </md-table-cell>
            <md-table-cell>
              <div class="md-layout" v-if="updatingAppliance === index">
                <md-field
                  :class="{
                    'md-invalid': errors.has('price'),
                  }"
                >
                  <label for="price"></label>
                  <md-input
                    name="price"
                    type="number"
                    v-model="asset.price"
                    v-validate="'required'"
                  ></md-input>
                  <span class="md-error">
                    {{ errors.first("price") }}
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item" v-else>{{ asset.price }}&nbsp;</div>
            </md-table-cell>
            <md-table-cell>{{ asset.updatedAt }}</md-table-cell>
            <md-table-cell>
              <div
                class="md-layout md-gutter"
                style="cursor: pointer"
                v-if="updatingAppliance === index"
              >
                <md-button
                  class="md-primary md-dense"
                  @click="updateAppliance(asset)"
                >
                  <md-icon class="md-primary">save</md-icon>
                  <span class="md-primary">
                    {{ $tc("words.save") }}
                  </span>
                </md-button>
                <md-button
                  class="md-accent md-dense"
                  @click="closeApplianceUpdate"
                >
                  <md-icon class="md-accent">close</md-icon>
                  <span class="md-accent">
                    {{ $tc("words.close") }}
                  </span>
                </md-button>
              </div>
              <div class="md-layout md-gutter" style="cursor: pointer" v-else>
                <md-button
                  class="md-primary md-dense"
                  @click="openApplianceUpdate(index)"
                >
                  <md-icon>edit</md-icon>
                  {{ $tc("words.edit") }}
                </md-button>
                <md-button
                  class="md-primary md-accent"
                  :disabled="loading"
                  @click="deleteAppliance(asset)"
                >
                  <md-icon class="md-accent">delete</md-icon>
                  {{ $tc("words.delete") }}
                </md-button>
              </div>
              <md-progress-bar md-mode="indeterminate" v-if="loading" />
            </md-table-cell>
          </md-table-row>
        </md-table>
      </widget>
    </div>
  </div>
</template>

<script>
import AssetTypeList from "@/modules/Assets/AssetTypeList.vue"
import Widget from "@/shared/Widget.vue"
import { ApplianceService } from "@/services/ApplianceService"
import { EventBus } from "@/shared/eventbus"
import AddAsset from "@/modules/Assets/AddAsset.vue"
import { notify } from "@/mixins/notify"

export default {
  name: "Assets",
  mixins: [notify],
  components: { AddAsset, AssetTypeList, Widget },
  data() {
    return {
      headers: [
        this.$tc("words.type"),
        this.$tc("words.name"),
        this.$tc("words.price"),
        this.$tc("phrases.lastUpdate"),
        "",
      ],
      applianceService: new ApplianceService(),
      addNewAsset: false,
      subscriber: "assetList",
      updatingAppliance: null,
      loading: false,
      resetKey: 0,
    }
  },
  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
    EventBus.$on("applianceAdded", (appliances) => {
      this.addNewAsset = false
      this.reloadList(this.subscriber, appliances)
    })
    EventBus.$on("addApplianceClosed", () => {
      this.addNewAsset = false
    })
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  methods: {
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) {
        return
      }
      this.applianceService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.applianceService.list.length,
      )
    },
    showAddAsset() {
      this.addNewAsset = true
    },
    async updateAppliance(assetType) {
      let validator = await this.$validator.validateAll()
      if (!validator) {
        return
      }
      this.loading = true
      this.$swal({
        type: "question",
        title: "Update Appliance",
        text: "Are you sure to update the appliance?",
        showCancelButton: true,
        cancelButtonText: this.$tc("words.cancel"),
        confirmButtonText: this.$tc("words.update"),
      }).then(async (response) => {
        if (response.value) {
          this.updateAppliance = false
          try {
            await this.applianceService.updateAppliance(assetType)
            this.alertNotify("success", "Appliance updated Successfully.")
            this.resetKey++
            this.updatingAppliance = null
          } catch (e) {
            this.alertNotify("error", e.message)
          }
        }
      })
      this.loading = false
    },
    async deleteAppliance(asset) {
      this.$swal({
        type: "question",
        title: this.$tc("phrases.deleteAppliance", 0),
        text: this.$tc("phrases.deleteAppliance", 2),
        showCancelButton: true,
        cancelButtonText: this.$tc("words.cancel"),
        confirmButtonText: this.$tc("words.delete"),
      }).then(async (response) => {
        if (response.value) {
          try {
            this.loading = true
            await this.applianceService.deleteAppliance(asset)
            this.loading = false
            this.alertNotify("success", this.$tc("phrases.deleteAppliance", 1))
            this.resetKey++
          } catch (e) {
            this.loading = false
            this.alertNotify("error", e.message)
          }
        }
      })
    },
    openApplianceUpdate(index) {
      if (this.updatingAppliance === index) {
        this.updatingAppliance = null
      } else {
        this.updatingAppliance = index
      }
    },
    closeApplianceUpdate() {
      this.updatingAppliance = null
    },
    closeAddComponent(data) {
      this.addNewAssetType = data
    },
  },
}
</script>

<style scoped></style>
