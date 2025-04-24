<template>
  <widget :title="$tc('Meter Detail')" color="green">
    <div class="meter-overview-card">
      <div
        class="meter-overview-detail"
        v-if="meter !== null && meter.loaded === true"
      >
        <md-list class="md-double-line">
          <md-list-item>
            <div class="list-column">
              <span>{{ $tc("words.manufacturer") }}</span>
              <span class="list-item-label">
                {{ meter.manufacturer.name }} ({{ meter.manufacturer.website }})
              </span>
            </div>
          </md-list-item>
          <md-divider></md-divider>

          <md-list-item>
            <div class="list-column">
              <span>{{ $tc("phrases.serialNumber") }}</span>
              <span class="list-item-label">{{ meter.serialNumber }}</span>
            </div>
          </md-list-item>
          <md-divider></md-divider>

          <md-list-item>
            <div class="list-column">
              <span>{{ $tc("words.tariff") }}</span>
              <span class="list-item-label" v-if="editTariff === false">
                {{ meter.tariff.name }}
                <span
                  style="cursor: pointer"
                  @click="editTariff = true"
                  v-if="meter.tariff.factor !== 2"
                >
                  <md-icon>edit</md-icon>
                </span>
              </span>
              <div v-else>
                <md-field>
                  <label for="tariff">
                    {{ $tc("words.tariff") }}
                  </label>
                  <md-select name="tariff" v-model="newTariffId">
                    <md-option
                      v-for="tariff in tariffService.list"
                      :key="tariff.id"
                      :value="tariff.id"
                    >
                      {{ tariff.name }}
                      <small>({{ moneyFormat(tariff.price) }})</small>
                    </md-option>
                  </md-select>
                </md-field>
                <div class="edit-actions">
                  <md-button class="md-icon-button" @click="updateTariff()">
                    <md-icon class="md-primary">save</md-icon>
                  </md-button>
                  <md-button class="md-icon-button" @click="editTariff = false">
                    <md-icon class="md-accent">cancel</md-icon>
                  </md-button>
                </div>
              </div>
            </div>
          </md-list-item>
          <md-divider></md-divider>

          <md-list-item>
            <div class="list-column">
              <span>{{ $tc("phrases.connectionGroup") }}</span>
              <span v-if="!editConnectionGroup" class="list-item-label">
                {{ meter.connectionGroup.name }}
                <span
                  style="cursor: pointer"
                  @click="editConnectionGroup = true"
                >
                  <md-icon>edit</md-icon>
                </span>
              </span>
              <div v-else>
                <md-field>
                  <label for="connectionGroup">
                    {{ $tc("phrases.connectionGroup") }}
                  </label>
                  <md-select
                    name="connectionGroup"
                    v-model="newConnectionGroupId"
                  >
                    <md-option
                      v-for="connectionGroup in connectionGroupService.list"
                      :key="connectionGroup.id"
                      :value="connectionGroup.id"
                    >
                      {{ connectionGroup.name }}
                    </md-option>
                  </md-select>
                </md-field>
                <div class="edit-actions">
                  <md-button
                    class="md-icon-button"
                    @click="updateConnectionGroup()"
                  >
                    <md-icon class="md-primary">save</md-icon>
                  </md-button>
                  <md-button
                    class="md-icon-button"
                    @click="editConnectionGroup = false"
                  >
                    <md-icon class="md-accent">cancel</md-icon>
                  </md-button>
                </div>
              </div>
            </div>
          </md-list-item>
          <md-divider></md-divider>

          <md-list-item>
            <div class="list-column">
              <span>{{ $tc("phrases.connectionType") }}</span>
              <span v-if="editConnectionType === false" class="list-item-label">
                {{ meter.connectionType.name }}
                <span
                  style="cursor: pointer"
                  @click="editConnectionType = true"
                >
                  <md-icon>edit</md-icon>
                </span>
              </span>
              <div v-else>
                <md-field>
                  <label for="connectionType">
                    {{ $tc("phrases.connectionType") }}
                  </label>
                  <md-select
                    name="connectionType"
                    v-model="newConnectionTypeId"
                  >
                    <md-option
                      v-for="connectionType in connectionTypeService.list"
                      :key="connectionType.id"
                      :value="connectionType.id"
                    >
                      {{ connectionType.name }}
                    </md-option>
                  </md-select>
                </md-field>
                <div class="edit-actions">
                  <md-button
                    class="md-icon-button"
                    @click="updateConnectionType()"
                  >
                    <md-icon class="md-primary">save</md-icon>
                  </md-button>
                  <md-button
                    class="md-icon-button"
                    @click="editConnectionType = false"
                  >
                    <md-icon class="md-accent">cancel</md-icon>
                  </md-button>
                </div>
              </div>
            </div>
          </md-list-item>
        </md-list>
      </div>
    </div>
  </widget>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { TariffService } from "@/services/TariffService"
import { ConnectionTypeService } from "@/services/ConnectionTypeService"
import { ConnectionGroupService } from "@/services/ConnectionGroupService"
import { MeterService } from "@/services/MeterService"
import { currency } from "@/mixins/currency"
import { notify } from "@/mixins/notify"

export default {
  name: "MeterDetail",
  mixins: [currency, notify],
  components: { Widget },
  props: {
    meter: {
      type: Object,
    },
  },
  mounted() {
    this.getTariffs()
    this.getConnectionGroups()
    this.getConnectionTypes()
    this.$emit("widget-loaded", "meter-detail")
  },
  data() {
    return {
      editTariff: false,
      newTariffId: null,
      meterService: new MeterService(),
      tariffService: new TariffService(),
      connectionTypeService: new ConnectionTypeService(),
      connectionGroupService: new ConnectionGroupService(),
      newConnectionGroupId: null,
      newConnectionTypeId: null,
      editConnectionGroup: false,
      editConnectionType: false,
      editSubConnectionType: false,
    }
  },
  methods: {
    async getTariffs() {
      try {
        await this.tariffService.getTariffs()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getConnectionGroups() {
      try {
        await this.connectionGroupService.getConnectionGroups()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getConnectionTypes() {
      try {
        await this.connectionTypeService.getConnectionTypes()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async updateTariff() {
      this.$emit("updated", {
        id: this.meter.id,
        tariffId: this.newTariffId,
      })
      this.editTariff = false
    },
    async updateConnectionGroup() {
      this.$emit("updated", {
        id: this.meter.id,
        connectionGroupId: this.newConnectionGroupId,
      })
      this.editConnectionGroup = false
    },
    async updateConnectionType() {
      this.$emit("updated", {
        id: this.meter.id,
        connectionTypeId: this.newConnectionTypeId,
      })
      this.editConnectionType = false
    },
  },
}
</script>

<style scoped>
.list-column {
  display: flex;
  flex-direction: column;
  width: 100%;
}
.list-item-label {
  font-size: 14px;
  color: rgba(0, 0, 0, 0.54);
  margin-bottom: 4px;
}

.list-item-value {
  font-size: 16px;
  color: rgba(0, 0, 0, 0.87);
  display: flex;
  align-items: center;
}
</style>
