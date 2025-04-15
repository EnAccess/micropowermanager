<template>
  <div>
    <widget
      :id="'connection-type-detail'"
      :title="$tc('phrases.connectionTypeDetails')"
      :subscriber="subscriber.detail"
    >
      <md-card>
        <md-card-content>
          <div class="md-layout md-gutter">
            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="md-layout">
                <div class="md-layout-item md-subheader">
                  {{ $tc("words.id") }}
                </div>
                <div class="md-layout-item md-subheader n-font">
                  {{ subConnectionType.connection_type_id }}
                </div>
              </div>
              <hr class="hr-d" />
              <div class="md-layout">
                <div class="md-layout-item md-subheader">
                  {{ $tc("words.name") }}
                </div>
                <div
                  v-if="editConnectionType"
                  class="md-layout-item md-subheader n-font"
                >
                  {{ connectionType.name }}
                  <md-button
                    class="md-icon-button"
                    @click="toggleEditConnectionType"
                  >
                    <md-icon>edit</md-icon>
                  </md-button>
                </div>
                <div v-else class="md-layout-item md-subheader n-font">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('phrases.connectionType')),
                    }"
                  >
                    <label for="connectionTypeName">
                      {{ $tc("phrases.editConnectionType") }}
                    </label>
                    <md-input
                      id="connectionTypeName"
                      :name="$tc('phrases.connectionType')"
                      v-model="connectionType.name"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first($tc("phrases.connectionType")) }}
                    </span>
                    <md-button
                      class="md-icon-button"
                      @click="editConnectionTypeName"
                    >
                      <md-icon>save</md-icon>
                    </md-button>
                    <md-button
                      class="md-icon-button"
                      @click="toggleEditConnectionType"
                    >
                      <md-icon>close</md-icon>
                    </md-button>
                  </md-field>
                </div>
              </div>
              <hr class="hr-d" />
              <div class="md-layout">
                <div class="md-layout-item md-subheader">
                  {{ $tc("phrases.createdAt") }}
                </div>
                <div class="md-layout-item md-subheader n-font">
                  {{ formatReadableDate(connectionType.created_at) }}
                </div>
              </div>
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
              <div class="md-layout">
                <div class="md-layout-item md-subheader">
                  {{ $tc("words.meter", 2) }}
                </div>
                <div class="md-layout-item md-subheader n-font">
                  {{ connectionType.meters_count }}
                </div>
              </div>
              <hr class="hr-d" />
              <div class="md-layout">
                <div class="md-layout-item md-subheader">
                  {{ $tc("phrases.subTypes") }}
                </div>
                <div class="md-layout-item md-subheader n-font">
                  {{ subConnectionTypeService.subConnectionTypes.length }}
                </div>
              </div>
            </div>
          </div>
        </md-card-content>
      </md-card>
    </widget>
    <widget
      :title="$tc('phrases.newSubConnectionType')"
      :id="'add-sub-connection-type'"
      color="red"
      v-if="showNewSubType"
    >
      <md-card>
        <md-card-content>
          <div class="md-layout md-gutter">
            <div class="md-layout-item md-size-50 md-small-size-100">
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
                  v-model="subConnectionType.name"
                  v-validate="'required|min:3'"
                />
                <span class="md-error">
                  {{ errors.first($tc("words.name")) }}
                </span>
              </md-field>
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('words.tariff')),
                }"
              >
                <label for="tariff">
                  {{ $tc("words.tariff") }}
                </label>
                <md-select
                  v-model="subConnectionType.tariff_id"
                  v-validate="'required'"
                  :name="$tc('words.tariff')"
                  id="tariff"
                >
                  <md-option v-for="t in tariff" :key="t.id" :value="t.id">
                    {{ t.name }}
                  </md-option>
                </md-select>
                <span class="md-error">
                  {{ errors.first($tc("words.tariff")) }}
                </span>
              </md-field>
            </div>
          </div>
        </md-card-content>

        <md-card-actions>
          <md-button
            role="button"
            class="md-raised md-primary"
            @click="saveSubType(subConnectionType)"
          >
            {{ $tc("words.save") }}
          </md-button>
          <md-button role="button" class="md-raised" @click="addSubType">
            {{ $tc("words.close") }}
          </md-button>
        </md-card-actions>
      </md-card>
    </widget>
    <widget
      :title="$tc('phrases.subConnectionTypes')"
      :id="'sub-connection-types'"
      :button="true"
      :button-text="$tc('phrases.newSubConnectionType')"
      @widgetAction="addSubType"
      :color="'green'"
      :subscriber="subscriber.subTypes"
    >
      <md-card>
        <md-card-content>
          <md-table>
            <md-table-row>
              <md-table-head>#</md-table-head>
              <md-table-head>{{ $tc("words.id") }}</md-table-head>
              <md-table-head>
                {{ $tc("words.name") }}
              </md-table-head>
              <md-table-head>
                {{ $tc("words.tariff") }}
              </md-table-head>
              <md-table-head></md-table-head>
            </md-table-row>
            <md-table-row
              v-for="(
                subType, index
              ) in subConnectionTypeService.subConnectionTypes"
              :key="index"
            >
              <md-table-cell>{{ index + 1 }}</md-table-cell>
              <md-table-cell>{{ subType.id }}</md-table-cell>
              <md-table-cell>
                <div v-if="editSubConnectionType === subType.id">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.name')),
                    }"
                  >
                    <label for="subConnectionTypeName">
                      {{ $tc("phrases.editSubConnectionType") }}
                    </label>
                    <md-input
                      id="subConnectionTypeName"
                      :name="$tc('words.name')"
                      v-model="subType.name"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first($tc("words.name")) }}
                    </span>
                  </md-field>
                </div>
                <div v-else>
                  {{ subType.name }}
                </div>
              </md-table-cell>
              <md-table-cell>
                <div v-if="editSubConnectionType === subType.id">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.tariff')),
                    }"
                  >
                    <label for="tariff">
                      {{ $tc("words.tariff") }}
                    </label>
                    <md-select
                      v-model="subType.tariff_id"
                      v-validate="'required'"
                      :name="$tc('words.tariff')"
                      id="tariff"
                    >
                      <md-option v-for="t in tariff" :key="t.id" :value="t.id">
                        {{ t.name }}
                      </md-option>
                    </md-select>
                    <span class="md-error">
                      {{ errors.first($tc("words.tariff")) }}
                    </span>
                  </md-field>
                </div>
                <div v-else>
                  {{ subType.tariff.name }}
                </div>
              </md-table-cell>
              <md-table-cell>
                <div v-if="editSubConnectionType === subType.id">
                  <md-button
                    class="md-icon-button"
                    @click="updateSubConnectionType(subType)"
                  >
                    <md-icon>save</md-icon>
                  </md-button>
                  <md-button
                    class="md-icon-button"
                    @click="editSubConnectionType = null"
                  >
                    <md-icon>close</md-icon>
                  </md-button>
                </div>
                <div v-else>
                  <md-button
                    class="md-icon-button"
                    @click="editSubConnectionType = subType.id"
                  >
                    <md-icon>edit</md-icon>
                  </md-button>
                </div>
              </md-table-cell>
            </md-table-row>
          </md-table>
        </md-card-content>
      </md-card>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { SubConnectionTypeService } from "@/services/SubConnectionTypeService"
import { ConnectionTypeService } from "@/services/ConnectionTypeService"
import { TariffService } from "@/services/TariffService"
import moment from "moment"
import { EventBus } from "@/shared/eventbus"
import { notify } from "@/mixins/notify"

export default {
  name: "ConnectionTypeDetail",
  mixins: [notify],
  components: { Widget },
  data() {
    return {
      subscriber: {
        detail: "connection-type-detail",
        subTypes: "sub-connection-types",
      },
      connectionTypeService: new ConnectionTypeService(),
      subConnectionTypeService: new SubConnectionTypeService(),
      tariffService: new TariffService(),
      showNewSubType: false,
      newConnectionTypeName: null,
      editConnectionType: true,
      editSubConnectionType: null,
      subConnectionType: {
        name: null,
        connection_type_id: null,
        tariff_id: null,
      },
      selectedTariff: {},
      connectionTypeId: null,
      connectionType: [],
      tariff: null,
    }
  },
  created() {
    this.subConnectionType.connection_type_id = this.$route.params.id
    this.getSubConnectionTypes(this.subConnectionType.connection_type_id)
    this.getConnectionTypeDetail(this.subConnectionType.connection_type_id)
    this.getTariffs()
  },
  methods: {
    checkConfirm(result) {
      return "value" in result
    },
    formatReadableDate(date) {
      return moment(date).format("MMMM Do YYYY, h:mm:ss a")
    },
    async updateSubConnectionType(subType) {
      let validator = await this.$validator.validateAll()
      if (!validator) {
        return
      }
      this.$swal({
        type: "question",
        title: this.$tc("phrases.editSubConnectionType"),
        text: this.$tc("phrases.editSubConnectionTypeNotify", 1),
        showCancelButton: true,
        cancelButtonText: this.$tc("words.no"),
        confirmButtonText: this.$tc("words.yes"),
      }).then((response) => {
        if (this.checkConfirm(response)) {
          try {
            this.subConnectionTypeService.updateSubConnectionType(subType)
            this.editSubConnectionType = null
            this.alertNotify(
              "success",
              this.$tc("phrases.editSubConnectionTypeNotify", 2),
            )
          } catch (e) {
            this.alertNotify("error", e.message)
          }
        } else {
          return
        }
      })
    },
    async editConnectionTypeName() {
      let validator = await this.$validator.validateAll()
      if (!validator) {
        return
      }
      this.$swal({
        type: "question",
        title: this.$tc("phrases.editConnectionType"),
        text: this.$tc("phrases.editConnectionTypeNotify", 2, {
          name: this.connectionType.name,
        }),
        showCancelButton: true,
        cancelButtonText: this.$tc("words.no"),
        confirmButtonText: this.$tc("words.yes"),
      }).then((response) => {
        if (this.checkConfirm(response)) {
          try {
            this.connectionTypeService.updateConnectionType(this.connectionType)
            this.toggleEditConnectionType()
            this.alertNotify(
              "success",
              this.$tc("phrases.editConnectionTypeNotify", 1),
            )
          } catch (e) {
            this.alertNotify("error", e.message)
          }
        } else {
          return
        }
      })
    },
    toggleEditConnectionType() {
      this.editConnectionType = !this.editConnectionType
      this.newConnectionTypeName = this.connectionType.name
    },
    toggleEditSubConnectionType() {
      this.editSubConnectionType = !this.editConnectionType
    },
    addSubType() {
      this.showNewSubType = !this.showNewSubType
    },
    clearForm() {
      this.subConnectionType.name = null
      this.subConnectionType.tariff_id = null
    },
    async saveSubType(subConnectionType) {
      let validator = await this.$validator.validateAll()
      if (!validator) {
        return
      }
      try {
        await this.subConnectionTypeService.createSubConnectionType(
          subConnectionType,
        )
        this.alertNotify("success", this.$tc("phrases.newSubConnectionType", 2))
        this.addSubType()
        this.clearForm()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getTariffs() {
      try {
        this.tariff = await this.tariffService.getTariffs()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getConnectionTypeDetail(connectionTypeId) {
      try {
        this.connectionType =
          await this.connectionTypeService.getConnectionTypeDetail(
            connectionTypeId,
          )
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber.detail,
          this.connectionType,
        )
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getSubConnectionTypes(connectionTypeId) {
      try {
        await this.subConnectionTypeService.getSubConnectionTypes(
          connectionTypeId,
        )
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber.subTypes,
          this.subConnectionTypeService.subConnectionTypes.length,
        )
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>

<style scoped>
.n-font {
  font-weight: 100 !important;
}

.hr-d {
  height: 1pt;
  margin: auto;
  padding: 0;
  display: block;
  border: 0;
  /* transition: margin-left .3s cubic-bezier(.4,0,.2,1); */
  /* will-change: margin-left; */
  background-color: rgba(0, 0, 0, 0.12);
}
</style>
