import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import BlockCompletedBadge from '@/components/morning-hub/BlockCompletedBadge.vue'
import PlaceholderBlock from '@/components/morning-hub/PlaceholderBlock.vue'
import type { RoutineBlock } from '@/types'

function makeBlock(): RoutineBlock {
    return {
        id: 1,
        type: 'custom',
        name: 'Plan the day',
        sort_order: 0,
        timer_minutes: 5,
        clickup_connection_id: null,
        google_calendar_connection_id: null,
        config: null,
    }
}

function mountBlock(isCompleted: boolean) {
    return mount(PlaceholderBlock, {
        props: {
            block: makeBlock(),
            isActiveBlock: false,
            isCompleted,
            isTimerRunning: false,
            isTimerExpired: false,
            remainingSeconds: 0,
            formattedTime: '',
        },
    })
}

beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
})

describe('BlockCompletedBadge', () => {
    it('renders a check icon with an accessible label', () => {
        const wrapper = mount(BlockCompletedBadge)

        expect(wrapper.attributes('role')).toBe('img')
        expect(wrapper.attributes('aria-label')).toBeTruthy()
        expect(wrapper.find('svg').exists()).toBe(true)
    })
})

describe('routine block completion treatment', () => {
    it('shows the completed badge and dims the card when completed', () => {
        const wrapper = mountBlock(true)

        expect(wrapper.findComponent(BlockCompletedBadge).exists()).toBe(true)
        expect(wrapper.html()).toContain('opacity-75')
    })

    it('shows no badge and full opacity when not completed', () => {
        const wrapper = mountBlock(false)

        expect(wrapper.findComponent(BlockCompletedBadge).exists()).toBe(false)
        expect(wrapper.html()).not.toContain('opacity-75')
    })
})
