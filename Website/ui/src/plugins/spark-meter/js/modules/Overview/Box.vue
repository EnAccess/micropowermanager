<template>

    <md-card class="card-list-item-content">
        <div class="card-list-item-header">
            <div
                class="box"
                style="font-size: 1.5rem"
                :style="backgroundColor">
                <md-icon style="color:white;" v-if="!customIcon">{{ boxIcon }}</md-icon>
                <img :src="boxIcon" alt="" v-else>
                <slot></slot>
            </div>
        </div>
        <md-card-content>

            <div class="information" style="text-align:end">
                <div
                    class="header-text"
                    v-if="headerText"
                    v-text="headerText"

                ></div>


                <small
                    class="sub-text"
                    v-if="subText"
                    v-html="subText"
                    :style="'color:' + boxIconColor"
                ></small>
            </div>


        </md-card-content>
    </md-card>


</template>

<script>
export default {
    name: 'Box',
    props: {
        color: {
            type: Array,
            default: () => ['#c3c3c3', '#e1e1e1']
        },
        centerText: {
            type: Boolean,
            default: false
        },
        headerText: String,
        headerTextColor: {
            type: String,
            default: '#000'
        },
        subText: String,
        subTextColor: {
            type: String,
            default: '#525252'
        },
        boxIcon: String,
        customIcon: {
            type: Boolean,
            default: false
        },
        boxIconColor: String
    },
    computed: {
        backgroundColor: function () {
            let color =
                    'background:linear-gradient(135deg,' +
                    this.color[0] +
                    ' 0%, ' +
                    this.color[1] +
                    ' 100%)'
            return color
        }
    }
}
</script>

<style>
    .box {
        border-radius: 5px;
        padding: 30px;
        box-shadow: 0 12px 20px -10px rgba(53, 53, 53, 0.3), 0 4px 20px 0 rgba(0, 0, 0, .12), 0 7px 8px -5px rgb(142, 142, 142)
    }

    .header-text {
        color: rgb(148, 148, 148);
        margin-top: 0px;
        margin-bottom: 1rem;

    }

    .sub-text {
        font-weight: bold;
        font-size: 1.6rem;
    }

    .box-icon {
        font-size: 4rem;
    }

    .text-center {
        text-align: center;
    }

    .card-list {
        display: -webkit-inline-box !important;
        width: 100%;
    }

    .card-list-item {
        width: 100% !important;
    }

    .card-list-item-content {
        width: 100% !important;

    }

    .card-list-item-header {
        margin-top: -1.8rem;
        margin-left: 15px;
        float: left
    }

    @media screen and (max-width: 479px) {
        .card-list-item-content {
            margin-bottom: 1rem !important;
            min-height: unset;
        }
    }

    @media screen and (min-width: 640px) {
        .card-list-item-content {

            min-height: 100% !important;
        }
    }
</style>

