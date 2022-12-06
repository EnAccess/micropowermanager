<template>
    <widget
        :id="'asset_type_list'"

        :title="$tc('phrases.appliance',1)"
        :button="true"
        :buttonText="'New Type'"
        :paginator="assets.paginator"
        :subscriber="subscriber"
        :callback="newAsset"
        :route_name="'/assets/types'"
    >
        <md-table id="dt-basic" class="table table-striped table-bordered table-hover no-footer">
            <md-table-row>

                <md-table-head>ID
                </md-table-head>


                <md-table-head>
                    <i class="fa fa-fw fa-exchange-alt text-muted hidden-md hidden-sm hidden-xs"></i>
                    Name
                </md-table-head>

                <md-table-head class="hidden-xs">
                    <i class="fa fa-fw fa-clock text-muted hidden-md hidden-sm hidden-xs"></i>
                    Created At
                </md-table-head>


            </md-table-row>

            <md-table-row v-for="asset in assets.list" :key="asset.id" style="cursor:pointer;">
                <md-table-cell> {{ asset.id}}</md-table-cell>

                <md-table-cell> {{asset.type}}

                </md-table-cell>

                <md-table-cell class="hidden-xs">{{asset.created_at}}</md-table-cell>

            </md-table-row>

        </md-table>
    </widget>
</template>

<script>

import {AssetTypes} from '@/classes/asset/AssetTypes'
import {EventBus} from '@/shared/eventbus'
import Widget from '../../shared/widget'

export default {
    name: 'AssetTypes',
    components: {Widget},
    data() {
        return {
            assets: new AssetTypes(),
            subscriber: 'asset-list',
        }
    },
    mounted() {

        EventBus.$on('pageLoaded', this.reloadList)
        // EventBus.$on('searching', this.searching)
        // EventBus.$on('end_searching', this.endSearching)

    },
    beforeDestroy() {
        EventBus.$off('pageLoaded', this.reloadList)
        //EventBus.$off('searching', this.searching)
        //EventBus.$off('end_searching', this.endSearching)
    },
    methods: {
        reloadList(subscriber, data) {
            if (subscriber !== this.subscriber) return
            this.assets.updateList(data)

        },
        newAsset() {
        }
    }
}
</script>

<style>

</style>
