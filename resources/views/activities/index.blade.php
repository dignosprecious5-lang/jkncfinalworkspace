@extends('layouts.app')
@section('title', 'Activities')

@section('content')
<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #cbd5e1;
    }
</style>
<div class="flex w-full" x-data="activitiesData()">


    <!-- Left Sidebar -->
    <aside class="w-52 shrink-0 bg-white border-r border-gray-200 min-h-[calc(100vh-4rem)] flex flex-col pt-4">
        <div class="px-3 pb-2">
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest px-2 mb-1">Activities</p>
        </div>
        <nav class="flex flex-col gap-0.5 px-2">
            <button @click="activeTab = 'task'; selectedTaskDetails = null; selectedMeetingDetails = null; showVideoPlayer = false"
                    :class="activeTab === 'task' ? 'bg-blue-50 text-[#1d54e2] font-semibold border border-blue-100' : 'text-gray-700 hover:bg-gray-100'"
                    class="w-full text-left px-3 py-2 rounded-lg text-sm transition-colors">
                Task
            </button>
            <button @click="activeTab = 'events'; selectedTaskDetails = null; selectedMeetingDetails = null; showVideoPlayer = false"
                    :class="activeTab === 'events' ? 'bg-blue-50 text-[#1d54e2] font-semibold border border-blue-100' : 'text-gray-700 hover:bg-gray-100'"
                    class="w-full text-left px-3 py-2 rounded-lg text-sm transition-colors">
                Events
            </button>
            <button @click="activeTab = 'call'; selectedTaskDetails = null; selectedMeetingDetails = null; showVideoPlayer = false"
                    :class="activeTab === 'call' ? 'bg-blue-50 text-[#1d54e2] font-semibold border border-blue-100' : 'text-gray-700 hover:bg-gray-100'"
                    class="w-full text-left px-3 py-2 rounded-lg text-sm transition-colors">
                Call
            </button>
            <button @click="activeTab = 'meetings'; selectedTaskDetails = null; selectedMeetingDetails = null; showVideoPlayer = false"
                    :class="activeTab === 'meetings' ? 'bg-blue-50 text-[#1d54e2] font-semibold border border-blue-100' : 'text-gray-700 hover:bg-gray-100'"
                    class="w-full text-left px-3 py-2 rounded-lg text-sm transition-colors">
                Meetings
            </button>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-4">

    <!-- Main Card Container -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm mb-6">
        
        <!-- Toolbar -->
        <div x-show="activeTab !== 'meetings'" class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <!-- Left Toolbar -->
            <div class="flex items-center gap-3">

                
                <div class="relative" x-data="{ 
                        allTaskDropdownOpen: false, 
                        allTaskTab: 'all_views' 
                    }" 
                    @click.away="allTaskDropdownOpen = false">
                    
                    <button @click="allTaskDropdownOpen = !allTaskDropdownOpen" class="flex items-center gap-2 px-3 py-1.5 text-sm font-semibold text-gray-700 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors">
                        <span x-text="filterSelection[activeTab]">All Tasks</span>
                        <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform duration-200" :class="allTaskDropdownOpen ? 'rotate-180' : ''"></i>
                    </button>
                    
                    <!-- All Task Dropdown Panel -->
                    <div x-show="allTaskDropdownOpen" 
                         style="display: none;" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute left-0 mt-2 w-64 rounded-xl shadow-xl bg-white ring-1 ring-blue-500 z-50 border border-blue-400 overflow-hidden">
                        
                        <div class="flex flex-col h-full max-h-[400px]">
                            
                            <!-- Content Area -->
                            <div class="flex-1 overflow-y-auto py-2">
                                <!-- All Views Content -->
                                <div>
                                    
                                    <div x-show="activeTab === 'task'" class="flex flex-col" role="menu" aria-orientation="vertical">
                                        <a href="#" @click.prevent="filterSelection.task = 'All Tasks'; allTaskDropdownOpen = false" class="block px-8 py-2 text-sm text-gray-700 hover:bg-gray-100">All Tasks</a>
                                        <a href="#" @click.prevent="filterSelection.task = 'Open Tasks'; allTaskDropdownOpen = false" class="block px-8 py-2 text-sm text-gray-700 hover:bg-gray-100">Open Tasks</a>
                                        <a href="#" @click.prevent="filterSelection.task = 'Closed Tasks'; allTaskDropdownOpen = false" class="block px-8 py-2 text-sm text-gray-700 hover:bg-gray-100">Closed Tasks</a>
                                        <a href="#" @click.prevent="filterSelection.task = 'Overdue Tasks'; allTaskDropdownOpen = false" class="block px-8 py-2 text-sm text-gray-700 hover:bg-gray-100">Overdue Tasks</a>
                                    </div>
                                    
                                    <div x-show="activeTab === 'events'" style="display: none;" class="flex flex-col" role="menu" aria-orientation="vertical">
                                        <a href="#" @click.prevent="filterSelection.events = 'All Events'; allTaskDropdownOpen = false" class="block px-8 py-2 text-sm text-gray-700 hover:bg-gray-100">All Events</a>
                                        <a href="#" @click.prevent="filterSelection.events = 'Upcoming Events'; allTaskDropdownOpen = false" class="block px-8 py-2 text-sm text-gray-700 hover:bg-gray-100">Upcoming Events</a>
                                        <a href="#" @click.prevent="filterSelection.events = 'Closed Events'; allTaskDropdownOpen = false" class="block px-8 py-2 text-sm text-gray-700 hover:bg-gray-100">Closed Events</a>
                                    </div>
                                    
                                    <div x-show="activeTab === 'call'" style="display: none;" class="flex flex-col" role="menu" aria-orientation="vertical">
                                        <a href="#" @click.prevent="filterSelection.call = 'All Calls'; allTaskDropdownOpen = false" class="block px-8 py-2 text-sm text-gray-700 hover:bg-gray-100">All Calls</a>
                                        <a href="#" @click.prevent="filterSelection.call = 'Inbound Calls'; allTaskDropdownOpen = false" class="block px-8 py-2 text-sm text-gray-700 hover:bg-gray-100">Inbound Calls</a>
                                        <a href="#" @click.prevent="filterSelection.call = 'Outbound Calls'; allTaskDropdownOpen = false" class="block px-8 py-2 text-sm text-gray-700 hover:bg-gray-100">Outbound Calls</a>
                                        <a href="#" @click.prevent="filterSelection.call = 'Scheduled Calls'; allTaskDropdownOpen = false" class="block px-8 py-2 text-sm text-gray-700 hover:bg-gray-100">Scheduled Calls</a>
                                        <a href="#" @click.prevent="filterSelection.call = 'Overdue Calls'; allTaskDropdownOpen = false" class="block px-8 py-2 text-sm text-gray-700 hover:bg-gray-100">Overdue Calls</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Toolbar -->
            <div class="flex items-center gap-3">
                <!-- Primary Action Button -->
                <button @click="activeTab === 'call' ? showCallModal = true : (activeTab === 'events' ? showEventModal = true : (activeTab === 'meetings' ? showMeetingModal = true : showTaskModal = true))" class="flex items-center gap-2 px-4 py-1.5 bg-[#1d54e2] text-white rounded-full text-sm font-medium hover:bg-[#1541b0] transition shadow-sm ml-2 z-10">
                    <i class="fas fa-plus text-xs"></i>
                    <span x-text="activeTab === 'events' ? 'Event' : (activeTab === 'call' ? 'Call' : 'Task')">Task</span>
                </button>
                
                <!-- 3 Dots Export Menu -->
                <div class="relative ml-1" x-data="{ exportDropdownOpen: false }" @click.away="exportDropdownOpen = false">
                    <button @click="exportDropdownOpen = !exportDropdownOpen" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 rounded transition-colors">
                        <i class="fas fa-ellipsis-v text-sm"></i>
                    </button>

                    <div x-show="exportDropdownOpen" 
                         style="display: none;" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 top-full mt-2 w-52 rounded-2xl shadow-xl bg-white ring-1 ring-black ring-opacity-5 z-50 overflow-hidden">
                        <div class="py-2">
                            <button @click="showExportModal = true; exportDropdownOpen = false;" class="w-full text-left px-5 py-3 hover:bg-gray-50 flex items-center gap-3 text-sm text-gray-700 font-medium">
                                <i class="fas fa-download text-gray-500"></i>
                                Export this View
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div x-show="activeTab !== 'meetings'" class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead x-show="activeTab === 'task'">
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="py-3 px-4 text-xs font-semibold text-gray-600 w-10 text-center">
                            <input type="checkbox" x-model="selectAll" @change="selectedTasks = selectAll ? [...filteredTasksList] : []" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 transition-colors">
                        </th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider">Task Name</th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider">Due Date</th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider">Priority</th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider">Related Contact</th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider">Task Owner</th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <thead x-show="activeTab === 'events'" style="display: none;">
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="py-3 px-4 text-xs font-semibold text-gray-600 w-10 text-center">
                             <input type="checkbox" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 transition-colors">
                        </th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider group cursor-pointer">
                            Title <i class="fas fa-sort text-blue-400 text-[10px] ml-1 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider group cursor-pointer">
                            From <i class="fas fa-sort text-blue-400 text-[10px] ml-1 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider group cursor-pointer">
                            To <i class="fas fa-sort text-blue-400 text-[10px] ml-1 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider group cursor-pointer">
                            Related Contact <i class="fas fa-sort text-blue-400 text-[10px] ml-1 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider">Host</th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100" x-show="activeTab === 'task'">
                    <template x-for="task in filteredTasksList.slice((currentPage - 1) * perPage, currentPage * perPage)" :key="task.id">
                        <tr class="group transition-colors" :class="selectedTasks.includes(task.id) ? 'bg-blue-50/50' : 'hover:bg-gray-50'">
                            <td class="py-3 px-4 text-center">
                                <input type="checkbox" :value="task.id" x-model="selectedTasks" @change="selectAll = (selectedTasks.length === filteredTasksList.length && filteredTasksList.length > 0)" class="rounded text-blue-600 border-gray-300 transition-colors">
                            </td>
                            <td class="py-3 px-4 text-sm font-medium text-gray-900">
                                <div class="flex items-center gap-2">
                                    <span x-text="task.name"></span>
                                    <button @click="selectedTaskDetails = task; selectedTaskType = 'task'" class="text-gray-400 hover:text-blue-600 transition-opacity" title="View Task">
                                        <i class="far fa-eye text-sm"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600" x-text="task.due_date"></td>
                            <td class="py-3 px-4 text-sm text-gray-600" x-text="task.status"></td>
                            <td class="py-3 px-4 text-sm text-gray-600" x-text="task.priority"></td>
                            <td class="py-3 px-4 text-sm text-gray-600" x-text="task.related_to"></td>
                            <td class="py-3 px-4">
                                <template x-if="task.owner">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#D1F1DE] text-[#0A5632] rounded-full text-xs font-medium border border-[#BCE8CD]">
                                        <i class="fas fa-user-circle"></i>
                                        <span x-text="task.owner"></span>
                                    </div>
                                </template>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-2 transition-opacity">
                                    <div @click="editTask(task)" class="inline-flex items-center justify-center w-8 h-6 bg-blue-100 rounded text-blue-600 hover:bg-blue-200 transition cursor-pointer" title="Edit Task">
                                        <i class="fas fa-pencil-alt text-[10px]"></i>
                                    </div>
                                    <button @click="deleteActivity(task.id, 'tasks')" class="text-gray-400 hover:text-red-600 transition" title="Delete Task">
                                        <i class="far fa-trash-alt text-sm"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty filler rows for consistent height mapping to design -->
                    <template x-if="filteredTasksList.length < 5">
                        <template x-for="i in Array.from({length: 5 - filteredTasksList.length})">
                            <tr class="h-[48px] border-b border-gray-50"><td colspan="8"></td></tr>
                        </template>
                    </template>
                </tbody>
                
                <!-- Events Tab Body -->
                <tbody class="divide-y divide-gray-100" x-show="activeTab === 'events'" style="display: none;">
                    <template x-for="event in filteredEventsList.slice((currentPage - 1) * perPage, currentPage * perPage)" :key="event.id">
                        <tr class="group hover:bg-gray-50 transition-colors">
                            <td class="py-3 px-4 text-center">
                                <input type="checkbox" class="rounded border-gray-300 transition-colors">
                            </td>
                            <td class="py-3 px-4 text-sm font-medium text-gray-900">
                                <div class="flex items-center gap-2 pl-2">
                                    <span x-text="event.title"></span>
                                    <button @click="selectedTaskDetails = event; selectedTaskType = 'event'" class="text-gray-400 hover:text-blue-600 transition-opacity" title="View Event">
                                        <i class="far fa-eye text-sm"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600" x-text="event.from"></td>
                            <td class="py-3 px-4 text-sm text-gray-600" x-text="event.to"></td>
                            <td class="py-3 px-4 text-sm text-gray-600" x-text="event.related_to"></td>
                            <td class="py-3 px-4">
                                <template x-if="event.host">
                                    <div class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-[#D1F1DE] text-[#0A5632] rounded text-xs font-medium border border-[#BCE8CD]">
                                        <i class="fas fa-user text-[10px] text-[#0A5632]"></i> <span x-text="event.host"></span>
                                    </div>
                                </template>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div @click="editEvent(event)" class="inline-flex items-center justify-center w-8 h-6 bg-blue-100 rounded text-blue-600 hover:bg-blue-200 transition cursor-pointer" title="Edit Event">
                                        <i class="fas fa-pencil-alt text-[10px]"></i>
                                    </div>
                                    <button @click="deleteActivity(event.id, 'events')" class="text-gray-400 hover:text-red-600 transition-opacity" title="Delete Event">
                                        <i class="far fa-trash-alt text-sm"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <!-- Empty filler rows -->
                    <template x-if="filteredEventsList.length < 5">
                        <template x-for="i in Array.from({length: 5 - filteredEventsList.length})">
                            <tr class="h-[48px] border-b border-gray-50"><td colspan="7"></td></tr>
                        </template>
                    </template>
                </tbody>

                <!-- Call Tab Header -->
                <thead x-show="activeTab === 'call'" style="display: none;">
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="py-3 px-4 text-xs font-semibold text-gray-600 w-10 text-center">
                             <input type="checkbox" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300 transition-colors">
                        </th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider group cursor-pointer">
                            To
                        </th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider group cursor-pointer">
                            From
                        </th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider group cursor-pointer">
                            Call Type
                        </th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider group cursor-pointer">
                            Call Start Time
                        </th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider group cursor-pointer">
                            Call Duration
                        </th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider group cursor-pointer">
                            Related Contact
                        </th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider group cursor-pointer">
                            Purpose
                        </th>
                        <th class="py-3 px-4 text-xs font-bold text-gray-700 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>

                <!-- Call Tab Body -->
                <tbody class="divide-y divide-gray-100" x-show="activeTab === 'call'" style="display: none;">
                    <template x-for="call in filteredCallsList.slice((currentPage - 1) * perPage, currentPage * perPage)" :key="call.id">
                        <tr class="group hover:bg-gray-50 transition-colors">
                            <td class="py-3 px-4 text-center">
                                <input type="checkbox" class="rounded border-gray-300 transition-colors">
                            </td>
                            <td class="py-3 px-4 text-sm font-medium text-gray-900">
                                <div class="flex items-center gap-2">
                                    <span :class="call.completed ? 'line-through text-gray-400' : ''" x-text="call.to"></span>
                                    <button @click="selectedTaskDetails = call; selectedTaskType = 'call'" class="text-gray-400 hover:text-blue-600 transition-opacity" title="View Call">
                                        <i class="far fa-eye text-sm"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-sm font-medium text-gray-900">
                                <span :class="call.completed ? 'line-through text-gray-400' : ''" x-text="call.from"></span>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600" x-text="call.type"></td>
                            <td class="py-3 px-4 text-sm text-gray-600">
                                <div x-text="call.start_time"></div>
                                <div class="text-xs text-gray-400" x-text="call.start_hour"></div>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-600" x-text="call.duration"></td>
                            <td class="py-3 px-4 text-sm text-gray-600" x-text="call.related_to"></td>
                            <td class="py-3 px-4 text-sm text-gray-600" x-text="call.purpose"></td>

                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div @click="editCall(call)" class="inline-flex items-center justify-center w-8 h-6 bg-blue-100 rounded text-blue-600 hover:bg-blue-200 transition cursor-pointer" title="Edit Call">
                                        <i class="fas fa-pencil-alt text-[10px]"></i>
                                    </div>
                                    <button @click="deleteActivity(call.id, 'calls')" class="text-gray-400 hover:text-red-600 transition-opacity" title="Delete Call">
                                        <i class="far fa-trash-alt text-sm"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <!-- Empty filler rows -->
                    <template x-if="filteredCallsList.length < 5">
                        <template x-for="i in Array.from({length: 5 - filteredCallsList.length})">
                            <tr class="h-[48px] border-b border-gray-50"><td colspan="8"></td></tr>
                        </template>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Meetings Tab Content -->
        <div x-show="activeTab === 'meetings'" style="display: none;">
            
            <!-- Meetings Sub-Tabs & Schedule Button -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div class="flex items-center gap-1 bg-gray-100 rounded-full p-1">
                    <button @click="meetingsSubTab = 'all'" 
                            :class="meetingsSubTab === 'all' ? 'bg-[#1d54e2] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-200'"
                            class="px-5 py-1.5 rounded-full text-sm font-semibold transition-colors">
                        All Meetings
                    </button>
                    <button @click="meetingsSubTab = 'upcoming'" 
                            :class="meetingsSubTab === 'upcoming' ? 'bg-[#1d54e2] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-200'"
                            class="px-5 py-1.5 rounded-full text-sm font-semibold transition-colors">
                        Upcoming
                    </button>
                    <button @click="meetingsSubTab = 'completed'" 
                            :class="meetingsSubTab === 'completed' ? 'bg-[#1d54e2] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-200'"
                            class="px-5 py-1.5 rounded-full text-sm font-semibold transition-colors">
                        Completed
                    </button>
                </div>
                <button @click="showMeetingModal = true" class="flex items-center gap-2 px-5 py-2 bg-[#1d54e2] text-white rounded-full text-sm font-semibold hover:bg-[#1541b0] transition shadow-sm">
                    <i class="fas fa-plus text-xs"></i>
                    Schedule a Meeting
                </button>
            </div>

            <!-- Meetings Body: Sidebar + Cards -->
            <div class="flex min-h-[420px]">
                
                <!-- Filter Sidebar -->
                <div class="w-52 border-r border-gray-100 p-4 shrink-0">
                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-4">
                        <i class="fas fa-sliders-h text-gray-400"></i>
                        Filter
                    </div>
                    <div class="space-y-3">
                        <label class="flex items-center gap-2.5 cursor-pointer group">
                            <input type="checkbox" x-model="meetingFilters.videoRecording" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 transition">
                            <span class="text-sm text-gray-600 group-hover:text-gray-900 transition">Video Recording</span>
                        </label>
                        <label class="flex items-center gap-2.5 cursor-pointer group">
                            <input type="checkbox" x-model="meetingFilters.audioRecording" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 transition">
                            <span class="text-sm text-gray-600 group-hover:text-gray-900 transition">Audio Recording</span>
                        </label>
                        <label class="flex items-center gap-2.5 cursor-pointer group">
                            <input type="checkbox" x-model="meetingFilters.transcription" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 transition">
                            <span class="text-sm text-gray-600 group-hover:text-gray-900 transition">Transcription</span>
                        </label>
                        <label class="flex items-center gap-2.5 cursor-pointer group">
                            <input type="checkbox" x-model="meetingFilters.minutes" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 transition">
                            <span class="text-sm text-gray-600 group-hover:text-gray-900 transition">Minutes</span>
                        </label>
                    </div>
                </div>

                <!-- Meeting Cards Area -->
                <div class="flex-1 p-5 space-y-4 overflow-y-auto">
                    <template x-for="meeting in filteredMeetings.slice((currentPage - 1) * perPage, currentPage * perPage)" :key="meeting.id">
                        <div class="bg-white border border-gray-200 rounded-xl p-5 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <h4 class="text-base font-bold text-gray-900" x-text="meeting.title"></h4>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                                          :class="{
                                              'bg-emerald-100 text-emerald-700 border-emerald-200': meeting.status === 'upcoming',
                                              'bg-purple-100 text-purple-700 border-purple-200': meeting.status === 'completed'
                                          }"
                                          x-text="meeting.status"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="deleteActivity(meeting.id, 'meetings')" class="text-gray-400 hover:text-red-600 transition-colors mr-2" title="Delete Meeting">
                                        <i class="far fa-trash-alt text-sm"></i>
                                    </button>
                                    <button @click="selectedMeetingDetails = meeting" class="px-4 py-1.5 bg-[#1d54e2] text-white rounded-lg text-sm font-medium hover:bg-[#1541b0] transition">View Details</button>
                                </div>
                            </div>
                            <div class="flex items-center gap-5 text-sm text-gray-500 mb-3">
                                <span class="flex items-center gap-1.5">
                                    <i class="far fa-calendar text-gray-400"></i>
                                    <span x-text="meeting.date"></span>
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <i class="far fa-clock text-gray-400"></i>
                                    <span x-text="meeting.time"></span>
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <i class="fas fa-users text-gray-400"></i>
                                    <span x-text="meeting.attendees + ' Attendees'"></span>
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <template x-if="(meeting.has_video || meeting.has_audio) && meeting.status === 'upcoming' && activeRecordingMeetingId !== meeting.id && _scheduledTimers[meeting.id] !== undefined">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-xs font-medium border border-blue-200">
                                        <i class="far fa-clock text-[10px]"></i>
                                        Scheduled
                                    </span>
                                </template>
                                <template x-if="meeting.has_video">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 text-green-700 rounded-full text-xs font-medium border border-green-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Video Recording
                                    </span>
                                </template>
                                <template x-if="meeting.has_audio">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 text-green-700 rounded-full text-xs font-medium border border-green-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        Audio Recording
                                    </span>
                                </template>
                                <template x-if="meeting.has_transcript">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-gray-50 text-gray-600 rounded-full text-xs font-medium border border-gray-200">
                                        <i class="fas fa-file-alt text-gray-400 text-[10px]"></i>
                                        Transcript
                                    </span>
                                </template>
                                <template x-if="meeting.has_minutes">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-gray-50 text-gray-600 rounded-full text-xs font-medium border border-gray-200">
                                        <i class="far fa-file text-gray-400 text-[10px]"></i>
                                        Minutes
                                    </span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Footer / Pagination Area -->
        <div class="px-6 py-3 bg-white border-t border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3 text-xs font-semibold text-gray-500" x-show="activeTab === 'task'">
                <span class="text-gray-600">Total task <span class="text-gray-800" x-text="taskCounts.total"></span></span>
                <div class="w-px h-3 bg-gray-300"></div>
                <span class="text-gray-600">Open Task <span class="text-blue-600" x-text="taskCounts.open"></span></span>
                <div class="w-px h-3 bg-gray-300"></div>
                <span class="text-gray-600">Completed <span class="text-green-500" x-text="taskCounts.completed"></span></span>
                <div class="w-px h-3 bg-gray-300"></div>
                <span class="text-gray-600">Overdue <span class="text-red-500" x-text="taskCounts.overdue"></span></span>
            </div>

            <div class="flex items-center gap-3 text-xs font-semibold text-gray-500" x-show="activeTab === 'events'" style="display: none;">
                <span class="text-gray-600">Total Event <span class="text-gray-800" x-text="eventCounts.total"></span></span>
                <div class="w-px h-3 bg-gray-300"></div>
                <span class="text-gray-600">Upcoming Events <span class="text-blue-600" x-text="eventCounts.upcoming"></span></span>
                <div class="w-px h-3 bg-gray-300"></div>
                <span class="text-gray-600">Closed Events <span class="text-green-500" x-text="eventCounts.closed"></span></span>
            </div>

            <div class="flex items-center gap-3 text-xs font-semibold text-gray-500" x-show="activeTab === 'meetings'" style="display: none;">
                <span class="text-gray-600">Total Meetings <span class="text-gray-800" x-text="meetingCounts.total"></span></span>
                <div class="w-px h-3 bg-gray-300"></div>
                <span class="text-gray-600">Upcoming <span class="text-blue-600" x-text="meetingCounts.upcoming"></span></span>
                <div class="w-px h-3 bg-gray-300"></div>
                <span class="text-gray-600">Completed <span class="text-green-500" x-text="meetingCounts.completed"></span></span>
            </div>

            <div class="flex items-center gap-3 text-xs font-semibold text-gray-500" x-show="activeTab === 'call'" style="display: none;">
                <span class="text-gray-600">Total Calls <span class="text-gray-800" x-text="callCounts.total"></span></span>
                <div class="w-px h-3 bg-gray-300"></div>
                <span class="text-gray-600">Scheduled <span class="text-blue-600" x-text="callCounts.scheduled"></span></span>
                <div class="w-px h-3 bg-gray-300"></div>
                <span class="text-gray-600">Overdue <span class="text-red-500" x-text="callCounts.overdue"></span></span>
            </div>
            
            <div class="flex items-center gap-4 text-xs font-semibold text-gray-500 tracking-wide">
                <div class="flex items-center gap-2 relative" @click.away="perPageOpen = false">
                    <span class="text-gray-600">Records per page</span>
                    <button @click="perPageOpen = !perPageOpen" class="focus:outline-none flex items-center gap-1 text-gray-800 hover:text-black transition">
                        <span x-text="perPage"></span> <i class="fas fa-chevron-down text-[9px]" :class="perPageOpen ? 'rotate-180' : ''"></i>
                    </button>
                    <!-- Dropdown -->
                    <div x-show="perPageOpen" 
                         style="display: none;"
                         class="absolute bottom-full left-0 mb-1 w-20 bg-white rounded shadow-lg border border-gray-200 py-1 z-50 text-gray-800 text-xs font-normal">
                        <template x-for="option in perPageOptions" :key="option">
                            <button @click="perPage = option; perPageOpen = false" 
                                    class="w-full text-left px-3 py-1.5 hover:bg-gray-100 transition"
                                    :class="perPage === option ? 'bg-gray-100 font-bold text-black' : ''"
                                    x-text="option">
                            </button>
                        </template>
                    </div>
                </div>
                <div class="w-px h-3 bg-gray-300"></div>
                <span class="text-gray-600"><span class="text-gray-800" x-text="activeList.length > 0 ? ((currentPage - 1) * perPage) + 1 : '0'"></span> - <span class="text-gray-800" x-text="Math.min(currentPage * perPage, activeList.length)"></span> of <span class="text-gray-800" x-text="activeList.length"></span></span>
                <div class="flex items-center gap-3 text-gray-400">
                    <button @click="if(currentPage > 1) currentPage--" :disabled="currentPage === 1" class="hover:text-gray-800 transition disabled:opacity-50"><i class="fas fa-chevron-left text-[11px]"></i></button>
                    <button @click="if(currentPage * perPage < activeList.length) currentPage++" :disabled="currentPage * perPage >= activeList.length" class="hover:text-gray-800 transition disabled:opacity-50"><i class="fas fa-chevron-right text-[11px]"></i></button>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Backdrop -->
    <div x-show="showTaskModal || showMeetingModal || showCallModal || showEventModal || showExportModal || selectedTaskDetails || selectedMeetingDetails || showVideoPlayer"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="showTaskModal = false; showMeetingModal = false; showCallModal = false; showEventModal = false; showExportModal = false; selectedTaskDetails = null; selectedMeetingDetails = null; showVideoPlayer = false; newNoteContent = ''; editingNote = null;"
         class="fixed inset-0 bg-gray-900/40 backdrop-blur-[2px] z-[90]"
         style="display: none;">
    </div>

    <div x-show="showTaskModal" 
         style="display: none;" 
         class="fixed top-0 bottom-0 right-0 z-[100] w-full max-w-[420px] transition-all"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-8"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-8">
        
        <!-- Modal Panel -->
        <div class="bg-white shadow-2xl w-full h-full flex flex-col overflow-hidden border-l border-gray-200">
             
            <!-- Header -->
            <div class="px-5 py-4 flex items-center justify-between shrink-0 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Create Task</h3>
                <button @click="showTaskModal = false" class="text-gray-400 hover:text-gray-600 transition text-lg leading-none">&times;</button>
            </div>
            
            <!-- Body -->
            <div class="px-5 py-4 space-y-4 overflow-y-auto custom-scrollbar flex-1">

                <!-- Task Name -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Task Name <span class="text-red-500">*</span></label>
                    <input type="text" x-model="newTask.name" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition" />
                </div>

                <!-- Owner -->
                <div class="relative" x-data="{ ownerOpen: false }">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Owner</label>
                    <button @click="ownerOpen = !ownerOpen" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 flex items-center justify-between bg-white hover:border-[#1d54e2] transition outline-none">
                        <span x-text="newTask.owner || 'Select owner'"></span>
                        <i class="fas fa-chevron-down text-[10px] text-gray-400 transition-transform" :class="ownerOpen ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="ownerOpen" @click.away="ownerOpen = false" style="display:none"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg z-[110] py-1 overflow-hidden">
                        <template x-for="owner in systemUsers" :key="owner">
                            <button @click="newTask.owner = owner; ownerOpen = false"
                                    class="w-full text-left px-4 py-2 text-sm hover:bg-blue-50 hover:text-[#1d54e2] transition"
                                    :class="newTask.owner === owner ? 'bg-blue-50 text-[#1d54e2] font-semibold' : 'text-gray-700'"
                                    x-text="owner"></button>
                        </template>
                    </div>
                </div>

                <!-- Due Date -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Due Date</label>
                    <input type="date" x-model="newTask.dueDate" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition" />
                </div>

                <!-- Related To -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Related Contact</label>
                    <div class="relative" @click.away="closeRelatedPicker('tasks')">
                        <div class="flex flex-wrap gap-1.5 p-2 bg-white border border-gray-300 rounded-md focus-within:border-[#1d54e2] focus-within:ring-1 focus-within:ring-[#1d54e2] transition min-h-[40px]">
                            <template x-for="tag in newTask.relatedTo" :key="tag">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-medium border border-blue-200">
                                    <span x-text="tag"></span>
                                    <i @click="removeTag('tasks', tag)" class="fas fa-times cursor-pointer hover:text-blue-800"></i>
                                </span>
                            </template>
                            <input 
                                type="text" 
                                x-model="relatedSearch.tasks" 
                                @focus="openRelatedPicker('tasks')"
                                @input="openRelatedPicker('tasks')"
                                @keydown.enter.prevent="selectFirstRelatedOption('tasks')"
                                placeholder="Search Contacts/Companies..." 
                                class="flex-1 min-w-[150px] border-none p-0 text-sm text-gray-800 outline-none focus:ring-0 bg-transparent"
                            />
                        </div>
                        <div x-show="relatedPickerOpen.tasks" x-transition class="absolute left-0 right-0 top-full z-[120] mt-2 overflow-hidden rounded-xl border border-blue-100 bg-white shadow-2xl" style="display:none;">
                            <div class="border-b border-blue-50 bg-gradient-to-r from-blue-50 to-white px-4 py-2">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-blue-700">Select Related Record</p>
                            </div>
                            <div class="max-h-64 overflow-y-auto py-2">
                                <template x-for="option in filteredRelatedOptions('tasks')" :key="option.key">
                                    <button type="button" @click="selectRelatedOption('tasks', option)" class="flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-blue-50">
                                        <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold" :class="option.type === 'company' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'">
                                            <i class="fas" :class="option.type === 'company' ? 'fa-building' : 'fa-user'"></i>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-semibold text-gray-900" x-text="option.label"></span>
                                            <span class="block truncate text-xs text-gray-500" x-text="option.meta || (option.type === 'company' ? 'Company record' : 'Contact record')"></span>
                                        </span>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide" :class="option.type === 'company' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700'" x-text="option.type"></span>
                                    </button>
                                </template>
                                <div x-show="!filteredRelatedOptions('tasks').length" class="px-4 py-6 text-center text-sm text-gray-500">No matching contacts or companies found.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Description</label>
                    <textarea rows="3" x-model="newTask.description" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition resize-none"></textarea>
                </div>

                <!-- Checkboxes -->
                <div class="flex flex-col gap-2">
                    <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-600">
                        <input type="checkbox" x-model="newTask.priority" class="w-4 h-4 rounded border-gray-300 text-[#1d54e2] focus:ring-[#1d54e2]">
                        Mark as High Priority
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-600">
                        <input type="checkbox" x-model="newTask.completed" class="w-4 h-4 rounded border-gray-300 text-[#1d54e2] focus:ring-[#1d54e2]">
                        Mark as Completed
                    </label>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="px-5 py-4 flex items-center justify-between border-t border-gray-100 shrink-0">
                <button @click="showTaskModal = false" class="px-6 py-2 rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium transition">
                    Cancel
                </button>
                <button @click="saveTask()" class="px-6 py-2 rounded-md bg-[#1d54e2] text-white hover:bg-[#1541b0] text-sm font-semibold transition shadow-sm">
                    Save
                </button>
            </div>
        </div>
    </div>

    <div x-show="showMeetingModal" 
         style="display: none;" 
         class="fixed top-0 bottom-0 right-0 z-[100] w-full max-w-[420px] transition-all"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-8"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-8">
         
        <!-- Modal Panel -->
        <div class="bg-white shadow-2xl w-full h-full flex flex-col overflow-hidden border-l border-gray-200">
            
            <!-- Header -->
            <div class="px-5 py-4 flex items-center justify-between shrink-0 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Schedule New Meeting</h3>
                <button @click="showMeetingModal = false" class="text-gray-400 hover:text-gray-600 transition text-lg leading-none">&times;</button>
            </div>

            <!-- Body -->
            <div class="px-5 py-4 space-y-4 overflow-y-auto custom-scrollbar flex-1">
                
                <!-- Meeting Title -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Meeting Title <span class="text-red-500">*</span></label>
                    <input type="text" x-model="newMeeting.title" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition" />
                </div>

                <!-- Owner -->
                <div class="relative" x-data="{ ownerOpen: false }">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Owner</label>
                    <button @click="ownerOpen = !ownerOpen" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 flex items-center justify-between bg-white hover:border-[#1d54e2] transition outline-none">
                        <span x-text="newMeeting.owner || 'Select owner'"></span>
                        <i class="fas fa-chevron-down text-[10px] text-gray-400 transition-transform" :class="ownerOpen ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="ownerOpen" @click.away="ownerOpen = false" style="display:none"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg z-[110] py-1 overflow-hidden">
                        <template x-for="owner in systemUsers" :key="owner">
                            <button @click="newMeeting.owner = owner; ownerOpen = false"
                                    class="w-full text-left px-4 py-2 text-sm hover:bg-blue-50 hover:text-[#1d54e2] transition"
                                    :class="newMeeting.owner === owner ? 'bg-blue-50 text-[#1d54e2] font-semibold' : 'text-gray-700'"
                                    x-text="owner"></button>
                        </template>
                    </div>
                </div>

                <!-- Date & Time -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Date & Time</label>
                    <div class="flex gap-2">
                        <input type="date" x-model="newMeeting.date" class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition" />
                        <input type="time" x-model="newMeeting.time" class="w-32 border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition" />
                    </div>
                </div>

                <!-- Duration -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Duration</label>
                    <div class="flex gap-2">
                        <div class="flex-1 flex items-center border border-gray-300 rounded-md px-3 py-2 focus-within:border-[#1d54e2] focus-within:ring-1 focus-within:ring-[#1d54e2] transition">
                            <input type="number" min="0" x-model="newMeeting.durationHour" class="w-full bg-transparent text-sm text-gray-800 outline-none" placeholder="0" />
                            <span class="text-gray-400 text-sm ml-1">hr</span>
                        </div>
                        <div class="flex-1 flex items-center border border-gray-300 rounded-md px-3 py-2 focus-within:border-[#1d54e2] focus-within:ring-1 focus-within:ring-[#1d54e2] transition">
                            <input type="number" min="0" max="59" x-model="newMeeting.durationMin" class="w-full bg-transparent text-sm text-gray-800 outline-none" placeholder="30" />
                            <span class="text-gray-400 text-sm ml-1">mins</span>
                        </div>
                    </div>
                </div>

                <!-- Meeting Link / Location -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Meeting Link / Location</label>
                    <div class="relative">
                        <input type="text" x-model="newMeeting.location" placeholder="e.g. https://meet.google.com/..." class="w-full border border-gray-300 rounded-md px-3 py-2 pr-24 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition placeholder-gray-400" />
                        <button @click="newMeeting.location = 'https://meet.google.com/' + Math.random().toString(36).substring(2,5) + '-' + Math.random().toString(36).substring(2,6) + '-' + Math.random().toString(36).substring(2,5)" class="absolute right-1 top-1 bottom-1 px-3 text-xs bg-blue-50 text-[#1d54e2] font-semibold rounded hover:bg-blue-100 transition">Generate</button>
                    </div>
                </div>

                <!-- Related To -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Related Contact</label>
                    <div class="relative" @click.away="closeRelatedPicker('meetings')">
                        <div class="flex flex-wrap gap-1.5 p-2 bg-white border border-gray-300 rounded-md focus-within:border-[#1d54e2] focus-within:ring-1 focus-within:ring-[#1d54e2] transition min-h-[40px]">
                            <template x-for="tag in newMeeting.attendees" :key="tag">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-medium border border-blue-200">
                                    <span x-text="tag"></span>
                                    <i @click="removeTag('meetings', tag)" class="fas fa-times cursor-pointer hover:text-blue-800"></i>
                                </span>
                            </template>
                            <input 
                                type="text" 
                                x-model="relatedSearch.meetings" 
                                @focus="openRelatedPicker('meetings')"
                                @input="openRelatedPicker('meetings')"
                                @keydown.enter.prevent="selectFirstRelatedOption('meetings')"
                                placeholder="Search Contacts/Companies..." 
                                class="flex-1 min-w-[150px] border-none p-0 text-sm text-gray-800 outline-none focus:ring-0 bg-transparent"
                            />
                        </div>
                        <div x-show="relatedPickerOpen.meetings" x-transition class="absolute left-0 right-0 top-full z-[120] mt-2 overflow-hidden rounded-xl border border-blue-100 bg-white shadow-2xl" style="display:none;">
                            <div class="border-b border-blue-50 bg-gradient-to-r from-blue-50 to-white px-4 py-2">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-blue-700">Select Related Record</p>
                            </div>
                            <div class="max-h-64 overflow-y-auto py-2">
                                <template x-for="option in filteredRelatedOptions('meetings')" :key="option.key">
                                    <button type="button" @click="selectRelatedOption('meetings', option)" class="flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-blue-50">
                                        <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold" :class="option.type === 'company' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'">
                                            <i class="fas" :class="option.type === 'company' ? 'fa-building' : 'fa-user'"></i>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-semibold text-gray-900" x-text="option.label"></span>
                                            <span class="block truncate text-xs text-gray-500" x-text="option.meta || (option.type === 'company' ? 'Company record' : 'Contact record')"></span>
                                        </span>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide" :class="option.type === 'company' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700'" x-text="option.type"></span>
                                    </button>
                                </template>
                                <div x-show="!filteredRelatedOptions('meetings').length" class="px-4 py-6 text-center text-sm text-gray-500">No matching contacts or companies found.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Description / Agenda</label>
                    <textarea rows="3" x-model="newMeeting.description" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition resize-none"></textarea>
                </div>

                <!-- Recording & Documentation -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-2">Recording & Documentation</label>
                    <div class="flex flex-col gap-2">
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-600 select-none">
                            <input type="checkbox" x-model="newMeeting.hasVideo" class="w-4 h-4 rounded border-gray-300 text-[#1d54e2] focus:ring-[#1d54e2]">
                            Record Video
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-600 select-none">
                            <input type="checkbox" x-model="newMeeting.hasAudio" class="w-4 h-4 rounded border-gray-300 text-[#1d54e2] focus:ring-[#1d54e2]">
                            Record Audio
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-600"><input type="checkbox" x-model="newMeeting.hasTranscript" class="w-4 h-4 rounded border-gray-300 text-[#1d54e2] focus:ring-[#1d54e2]"> Generate transcript automatically</label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-600"><input type="checkbox" x-model="newMeeting.hasMinutes" class="w-4 h-4 rounded border-gray-300 text-[#1d54e2] focus:ring-[#1d54e2]"> Generate meeting minutes automatically</label>
                    </div>
                    <!-- Scheduling info note -->
                    <div class="mt-3 flex items-start gap-2 p-2.5 bg-blue-50 border border-blue-100 rounded-lg">
                        <i class="far fa-clock text-blue-400 mt-0.5 text-xs shrink-0"></i>
                        <p class="text-xs text-blue-600">Recording will start automatically at the scheduled date &amp; time and will continue until the page is closed or the meeting is deleted.</p>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="px-5 py-4 flex items-center justify-between border-t border-gray-100 shrink-0">
                <button @click="showMeetingModal = false" class="px-6 py-2 rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium transition">
                    Cancel
                </button>
                <button @click="saveMeeting()" class="px-6 py-2 rounded-md bg-[#1d54e2] text-white hover:bg-[#1541b0] text-sm font-semibold transition shadow-sm">
                    Save
                </button>
            </div>
        </div>
    </div>

    <!-- Task Details Slide-over Panel -->
    <div x-show="selectedTaskDetails"
         x-data="{ taskDetailsTab: 'information' }"
         style="display: none;"
         class="fixed top-0 bottom-0 right-0 z-[100] w-full max-w-[480px] transition-all"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-8"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-8">
         
        <div class="bg-white shadow-2xl w-full h-full flex flex-col overflow-hidden border-l border-gray-200">
            
            <!-- Header -->
            <div class="px-5 py-4 flex items-center justify-between shrink-0 border-b border-gray-100">
                <div>
                    <h3 class="text-base font-semibold text-gray-800" x-text="(selectedTaskType ? selectedTaskType.charAt(0).toUpperCase() + selectedTaskType.slice(1) : 'Task') + ' Details'">Task Details</h3>
                    <div class="flex items-center gap-2 text-xs text-gray-400 mt-0.5" x-text="selectedTaskDetails?.owner || selectedTaskDetails?.host || 'John Kelly'"></div>
                </div>
                <button @click="selectedTaskDetails = null; newNoteContent = ''; editingNote = null;" class="text-gray-400 hover:text-gray-600 transition text-lg leading-none">&times;</button>
            </div>

        <!-- Panel Body -->
        <div class="flex flex-col flex-1 bg-white">
            
            <!-- Tabs -->
            <div class="flex px-6 pt-2 border-b border-gray-100">
                <button @click="taskDetailsTab = 'information'" 
                        :class="taskDetailsTab === 'information' ? 'bg-[#1d54e2] text-white' : 'text-gray-500 hover:bg-gray-50'"
                        class="px-6 py-2 rounded-t-lg text-sm font-semibold transition">
                    Information
                </button>
                <button @click="taskDetailsTab = 'notes'" 
                        :class="taskDetailsTab === 'notes' ? 'bg-[#1d54e2] text-white font-semibold' : 'text-gray-500 hover:bg-gray-50 font-medium'"
                        class="px-6 py-2 rounded-t-lg text-sm transition">
                    Notes
                </button>
            </div>

            <!-- Content Area -->
            <div class="p-6 overflow-y-auto flex-1">
                
                <!-- Information Tab Content -->
                <div x-show="taskDetailsTab === 'information'">
                    <h4 class="font-bold text-gray-900 text-sm mb-4" x-text="(selectedTaskType ? selectedTaskType.charAt(0).toUpperCase() + selectedTaskType.slice(1) : 'Task') + ' Details'">Task Details</h4>
                
                <div class="space-y-4">
                    <!-- Detail Row -->
                    <div class="flex group">
                        <div class="w-32 text-sm text-gray-500">Date/Time</div>
                        <div class="flex-1 text-sm text-gray-900 group-hover:bg-gray-50 rounded px-1 -mx-1 transition">
                            <span x-text="selectedTaskDetails?.start_time || selectedTaskDetails?.due_date || selectedTaskDetails?.from || '-'"></span>
                        </div>
                    </div>
                    
                    <div class="flex group">
                        <div class="w-32 text-sm text-gray-500">Priority</div>
                        <div class="flex-1 text-sm text-gray-900 group-hover:bg-gray-50 rounded px-1 -mx-1 transition">
                            <span x-text="selectedTaskDetails?.priority || '-'"></span>
                        </div>
                    </div>

                    <div class="flex group">
                        <div class="w-32 text-sm text-gray-500">Status</div>
                        <div class="flex-1 text-sm text-gray-900 group-hover:bg-gray-50 rounded px-1 -mx-1 transition">
                            <span x-text="selectedTaskDetails?.status || selectedTaskDetails?.type || '-'"></span>
                        </div>
                    </div>

                    <div class="flex group">
                        <div class="w-32 text-sm text-gray-500">Reminder</div>
                        <div class="flex-1 text-sm text-gray-900 group-hover:bg-gray-50 rounded px-1 -mx-1 transition">
                            <span>-</span>
                        </div>
                    </div>

                    <div class="flex group">
                        <div class="w-32 text-sm text-gray-500">Description</div>
                        <div class="flex-1 text-sm text-gray-900 group-hover:bg-gray-50 rounded px-1 -mx-1 transition min-h-[20px]">
                            <span x-text="selectedTaskDetails?.description || selectedTaskDetails?.purpose || ''"></span>
                        </div>
                    </div>

                    <div class="flex group">
                        <div class="w-32 text-sm text-gray-500">Related To</div>
                        <div class="flex-1 text-sm text-gray-900 group-hover:bg-gray-50 rounded px-1 -mx-1 transition">
                            <span x-text="selectedTaskDetails?.related_to || '-'"></span>
                        </div>
                    </div>

                    <div class="flex group">
                        <div class="w-32 text-sm text-gray-500">Last Modified</div>
                        <div class="flex-1 text-sm text-gray-900 group-hover:bg-gray-50 rounded px-1 -mx-1 transition">
                            <span>Just now</span>
                        </div>
                    </div>
                </div>



                <!-- Call Recording Section -->
                <div class="mt-6 pt-4 border-t border-gray-100" x-show="selectedTaskType === 'call'">
                    <h4 class="font-bold text-gray-900 text-sm mb-3">Call Recording</h4>
                    
                    <!-- Persistent Custom Audio Player -->
                    <div x-show="selectedTaskDetails?.audio_path" class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-4 shadow-sm flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                    <i class="fas fa-microphone text-xs"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-gray-900 line-clamp-1" x-text="selectedTaskDetails?.audio_path ? selectedTaskDetails.audio_path.split('/').pop() : 'Recording'"></span>
                                    <span class="text-[10px] text-green-600 font-semibold uppercase tracking-wide">Recording Available</span>
                                </div>
                            </div>
                            <div class="flex gap-1">
                                <a :href="selectedTaskDetails?.audio_path" download class="w-8 h-8 rounded-full bg-white border border-gray-200 text-gray-500 hover:text-blue-600 hover:border-blue-200 flex items-center justify-center transition shadow-sm" title="Download">
                                    <i class="fas fa-download text-xs"></i>
                                </a>
                                <button @click="deleteCallAudio(selectedTaskDetails?.id)" class="w-8 h-8 rounded-full bg-white border border-gray-200 text-gray-500 hover:text-red-500 hover:border-red-200 flex items-center justify-center transition shadow-sm" title="Delete">
                                    <i class="far fa-trash-alt text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Controls -->
                        <div class="flex items-center gap-3">
                            <button @click="toggleCallAudio()" 
                                    class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 transition shrink-0 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1">
                                <i :class="isCallAudioPlaying ? 'fas fa-pause text-xs' : 'fas fa-play text-xs ml-0.5'"></i>
                            </button>
                            
                            <!-- Progress -->
                            <div class="flex flex-col flex-1 gap-1.5">
                                <div class="w-full h-2 bg-gray-200 rounded-full relative cursor-pointer overflow-hidden" @click="seekCallAudio($event)">
                                    <div class="absolute top-0 left-0 h-full bg-blue-500 rounded-full" 
                                         :style="`width: ${callAudioDuration > 0 ? (callAudioCurrentTime / callAudioDuration) * 100 : 0}%`"></div>
                                </div>
                                <div class="flex justify-between items-center text-[10px] text-gray-500 font-mono">
                                    <span x-text="formatTimeGeneral(callAudioCurrentTime)">00:00</span>
                                    <span x-text="formatTimeGeneral(callAudioDuration)">00:00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Recording State -->
                    <div x-show="!selectedTaskDetails?.audio_path" class="bg-gray-50 border border-gray-200 border-dashed rounded-xl p-5 text-center mb-4">
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-microphone-slash text-gray-400 text-sm"></i>
                        </div>
                        <p class="text-xs text-gray-500 font-medium">No recording available</p>
                    </div>

                    <!-- Hidden Audio Element -->
                    <audio x-ref="callAudioPlayer" 
                           :src="selectedTaskDetails?.audio_url"
                           @timeupdate="updateCallAudioTime()"
                           @loadedmetadata="updateCallAudioTime()"
                           @ended="isCallAudioPlaying = false"
                           class="hidden"></audio>

                    <!-- Upload UI -->
                    <div class="pt-2">
                        <button @click="document.getElementById('audioUploadInput').click()" 
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-50 border border-gray-200 text-gray-700 rounded-xl text-sm font-semibold hover:bg-white hover:border-[#1d54e2] hover:text-[#1d54e2] transition-all shadow-sm group"
                                :class="isUploadingAudio ? 'opacity-50 cursor-wait pointer-events-none' : ''">
                            <template x-if="!isUploadingAudio">
                                <i class="fas fa-upload text-xs group-hover:-translate-y-0.5 transition-transform"></i>
                            </template>
                            <template x-if="isUploadingAudio">
                                <i class="fas fa-spinner fa-spin text-xs"></i>
                            </template>
                            <span x-text="isUploadingAudio ? 'Uploading...' : (selectedTaskDetails?.audio_path ? 'Replace Recording' : 'Upload Call Recording')"></span>
                        </button>
                    </div>
                </div>

                <!-- Hidden Input -->
                <input type="file" 
                       id="audioUploadInput"
                       x-ref="audioUploadInput" 
                       class="hidden" 
                       accept="audio/*,video/mp4" 
                       @change="uploadCallAudio($event, selectedTaskDetails?.id)">
                </div>

                <!-- Notes Tab Content -->
                <div x-show="taskDetailsTab === 'notes'" style="display: none;">
                    <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 mb-4">
                        <textarea x-model="newNoteContent" class="w-full bg-transparent resize-none outline-none text-sm text-gray-900 placeholder-gray-400" rows="3" placeholder="Add a note..."></textarea>
                        <div class="flex justify-end mt-2">
                            <button @click="saveNote(selectedTaskDetails, selectedTaskType)" class="px-4 py-1.5 bg-[#1d54e2] text-white text-xs font-semibold rounded-lg hover:bg-[#1541b0] transition" x-text="editingNote ? 'Update Note' : 'Save Note'"></button>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <template x-for="note in selectedTaskDetails?.notes || []" :key="note.id">
                            <div class="flex gap-3">
                                <div class="w-8 h-8 rounded-full bg-[#1d54e2] text-white flex items-center justify-center text-xs font-bold shrink-0" x-text="(note.owner || 'JK').split(' ').map(n => n[0]).join('').toUpperCase()">
                                </div>
                                <div class="flex-1 text-sm bg-white p-3 rounded-lg border border-gray-100 shadow-sm relative group">
                                    <div class="flex justify-between items-start mb-1">
                                        <span class="font-semibold text-gray-900" x-text="note.owner"></span>
                                        <div class="flex items-center gap-2">
                                            <div class="opacity-0 group-hover:opacity-100 transition-opacity flex gap-2">
                                                <i @click="editNote(note)" class="fas fa-pencil-alt text-[10px] text-gray-400 hover:text-blue-600 cursor-pointer"></i>
                                                <i @click="deleteNote(note.id, selectedTaskDetails)" class="far fa-trash-alt text-[10px] text-gray-400 hover:text-red-500 cursor-pointer"></i>
                                            </div>
                                            <span class="text-xs text-gray-500" x-text="new Date(note.created_at).toLocaleDateString() === new Date().toLocaleDateString() ? 'Today' : new Date(note.created_at).toLocaleDateString()"></span>
                                        </div>
                                    </div>
                                    <p class="text-gray-700 whitespace-pre-line" x-text="note.content"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    </div>

    <!-- Meeting Details Slide-over Panel -->
    <div x-show="selectedMeetingDetails"
         style="display: none;"
         class="fixed top-0 bottom-0 right-0 z-[100] w-full max-w-[480px] transition-all"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-8"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-8">
         
        <div class="bg-white shadow-2xl w-full h-full flex flex-col overflow-hidden border-l border-gray-200">
            
            <!-- Header -->
            <div class="px-5 py-4 flex items-center justify-between shrink-0 border-b border-gray-100">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Meeting Details</h3>
                    <div class="flex items-center gap-2 text-xs text-gray-400 mt-0.5" x-text="selectedMeetingDetails?.owner"></div>
                </div>
                <button @click="selectedMeetingDetails = null; newNoteContent = ''; editingNote = null;" class="text-gray-400 hover:text-gray-600 transition text-lg leading-none">&times;</button>
            </div>

        <!-- Panel Body -->
        <div class="flex flex-col flex-1 bg-white">
            
            <!-- Tabs -->
            <div class="flex px-6 pt-2 border-b border-gray-100">
                <button @click="meetingDetailsTab = 'information'" 
                        :class="meetingDetailsTab === 'information' ? 'bg-[#1d54e2] text-white' : 'text-gray-500 hover:bg-gray-50'"
                        class="px-6 py-2 rounded-t-lg text-sm font-semibold transition">
                    Information
                </button>
                <button @click="meetingDetailsTab = 'notes'" 
                        :class="meetingDetailsTab === 'notes' ? 'bg-[#1d54e2] text-white font-semibold' : 'text-gray-500 hover:bg-gray-50 font-medium'"
                        class="px-6 py-2 rounded-t-lg text-sm transition">
                    Notes
                </button>
            </div>

            <!-- Content Area -->
            <div class="p-6 overflow-y-auto flex-1">
                
                <!-- Information Tab -->
                <div x-show="meetingDetailsTab === 'information'">
                    <h4 class="font-bold text-gray-900 text-sm mb-4">Basic Info</h4>
                
                    <div class="space-y-4">
                        <!-- Date & Time -->
                        <div class="flex group">
                            <div class="w-32 text-sm text-gray-500">Date & Time</div>
                            <div class="flex-1 text-sm text-gray-900 group-hover:bg-gray-50 rounded px-1 -mx-1 transition">
                                <span>
                                    <span x-text="selectedMeetingDetails?.date"></span>
                                    <span class="ml-2" x-text="selectedMeetingDetails?.time"></span>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Duration -->
                        <div class="flex group">
                            <div class="w-32 text-sm text-gray-500">Duration</div>
                            <div class="flex-1 text-sm text-gray-900 group-hover:bg-gray-50 rounded px-1 -mx-1 transition">
                                <span x-text="selectedMeetingDetails?.duration"></span>
                            </div>
                        </div>

                        <!-- Meeting Link / Location -->
                        <div class="flex group">
                            <div class="w-32 text-sm text-gray-500">Meeting Link / Location</div>
                            <div class="flex-1 text-sm text-gray-900 group-hover:bg-gray-50 rounded px-1 -mx-1 transition">
                                <span x-text="selectedMeetingDetails?.location"></span>
                            </div>
                        </div>

                        <!-- Attendees -->
                        <div class="flex group">
                            <div class="w-32 text-sm text-gray-500">Attendees</div>
                            <div class="flex-1 text-sm text-gray-900 group-hover:bg-gray-50 rounded px-1 -mx-1 transition">
                                <span x-text="selectedMeetingDetails?.attendees"></span>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="flex group">
                            <div class="w-32 text-sm text-gray-500">Status</div>
                            <div class="flex-1 text-sm text-gray-900 group-hover:bg-gray-50 rounded px-1 -mx-1 transition">
                                <span x-text="selectedMeetingDetails?.status"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <h4 class="font-bold text-gray-900 text-sm mt-6 mb-2">Description</h4>
                    <p class="text-sm text-gray-500" x-text="selectedMeetingDetails?.description"></p>

                    <!-- Download Options -->
                    <h4 class="font-bold text-gray-900 text-sm mt-6 mb-3">Download Options</h4>
                    <div class="space-y-3">
                        <!-- Video -->
                        <div class="flex items-center" x-show="selectedMeetingDetails?.has_video">
                            <div class="w-24 text-sm text-gray-500">Video</div>
                            <div class="flex items-center gap-2">
                                <button @click="downloadFile('video', 'mp4')" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#1d54e2] text-white rounded-lg text-xs font-semibold hover:bg-[#1541b0] transition shadow-sm"
                                        :class="selectedMeetingDetails?.status === 'upcoming' ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''">
                                    <i class="fas fa-download text-[10px]"></i>
                                    Download
                                </button>
                                <button @click="showVideoPlayer = true" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#1d54e2] text-white rounded-lg text-xs font-semibold hover:bg-[#1541b0] transition shadow-sm"
                                        :class="selectedMeetingDetails?.status === 'upcoming' ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''">
                                    Open
                                </button>
                            </div>
                        </div>
                        
                        <!-- Audio -->
                        <div class="flex items-center" x-show="selectedMeetingDetails?.has_audio">
                            <div class="w-24 text-sm text-gray-500">Audio</div>
                            <div class="flex items-center gap-2">
                                <button @click="downloadFile('audio', 'mp3')" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#1d54e2] text-white rounded-lg text-xs font-semibold hover:bg-[#1541b0] transition shadow-sm"
                                        :class="selectedMeetingDetails?.status === 'upcoming' ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''">
                                    <i class="fas fa-download text-[10px]"></i>
                                    Download
                                </button>
                            </div>
                        </div>

                        <!-- Transcript -->
                        <div class="flex items-center" x-show="selectedMeetingDetails?.has_transcript">
                            <div class="w-24 text-sm text-gray-500">Transcript</div>
                            <div class="relative">
                                <button @click="activeDownloadMenu = activeDownloadMenu === 'transcript' ? null : 'transcript'" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#1d54e2] text-white rounded-lg text-xs font-semibold hover:bg-[#1541b0] transition shadow-sm"
                                        :class="selectedMeetingDetails?.status === 'upcoming' ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''">
                                    <i class="fas fa-download text-[10px]"></i>
                                    Download
                                </button>
                                
                                <!-- Dropdown -->
                                <div x-show="activeDownloadMenu === 'transcript'" 
                                     @click.away="activeDownloadMenu = null"
                                     class="absolute left-0 mt-2 w-32 bg-white rounded-lg shadow-xl border border-gray-100 z-50 overflow-hidden" 
                                     style="display: none;">
                                    <button @click="downloadFile('transcript', 'pdf')" class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                        <i class="far fa-file-pdf text-red-500"></i> PDF (.pdf)
                                    </button>
                                    <button @click="downloadFile('transcript', 'docs')" class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2 border-t border-gray-50">
                                        <i class="far fa-file-word text-blue-500"></i> DOCS (.docx)
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Minutes -->
                        <div class="flex items-center" x-show="selectedMeetingDetails?.has_minutes">
                            <div class="w-24 text-sm text-gray-500">Minutes</div>
                            <div class="relative">
                                <button @click="activeDownloadMenu = activeDownloadMenu === 'minutes' ? null : 'minutes'" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#1d54e2] text-white rounded-lg text-xs font-semibold hover:bg-[#1541b0] transition shadow-sm"
                                        :class="selectedMeetingDetails?.status === 'upcoming' ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''">
                                    <i class="fas fa-download text-[10px]"></i>
                                    Download
                                </button>

                                <!-- Dropdown -->
                                <div x-show="activeDownloadMenu === 'minutes'" 
                                     @click.away="activeDownloadMenu = null"
                                     class="absolute left-0 mt-2 w-32 bg-white rounded-lg shadow-xl border border-gray-100 z-50 overflow-hidden" 
                                     style="display: none;">
                                    <button @click="downloadFile('minutes', 'pdf')" class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                        <i class="far fa-file-pdf text-red-500"></i> PDF (.pdf)
                                    </button>
                                    <button @click="downloadFile('minutes', 'docs')" class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2 border-t border-gray-50">
                                        <i class="far fa-file-word text-blue-500"></i> DOCS (.docx)
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Upload -->
                        <div class="pt-2">
                            <button @click="document.getElementById('videoUploadInput').click()" 
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-50 border border-gray-200 text-gray-700 rounded-xl text-sm font-semibold hover:bg-white hover:border-[#1d54e2] hover:text-[#1d54e2] transition-all shadow-sm group"
                                    :class="isUploading ? 'opacity-50 cursor-wait pointer-events-none' : ''">
                                <template x-if="!isUploading">
                                    <i class="fas fa-video text-xs group-hover:scale-110 transition-transform"></i>
                                </template>
                                <template x-if="isUploading">
                                    <i class="fas fa-spinner fa-spin text-xs"></i>
                                </template>
                                <span x-text="isUploading ? 'Uploading Video...' : 'Upload Video File'"></span>
                            </button>
                        </div>

                        <!-- Open Recording Button -->
                        <div class="pt-1" x-show="selectedMeetingDetails?.has_video">
                            <button @click="showVideoPlayer = true" 
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-[#1d54e2] text-white rounded-xl text-sm font-bold hover:bg-[#1541b0] transition-all shadow-md group">
                                <i class="fas fa-play-circle text-xs group-hover:scale-110 transition-transform"></i>
                                Open Recording
                            </button>
                        </div>


                        <!-- Manual Transcript / Minutes Upload -->
                        <div class="pt-4 border-t border-gray-100" x-show="selectedMeetingDetails?.status === 'completed'">
                            <div class="grid grid-cols-1 gap-2">
                                <button
                                    type="button"
                                    @click="document.getElementById('transcriptUploadInput').click()"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-50 border border-gray-200 text-gray-700 rounded-xl text-sm font-bold hover:bg-white hover:border-[#1d54e2] hover:text-[#1d54e2] transition-all shadow-sm"
                                    :class="isUploadingTranscript ? 'opacity-50 cursor-wait pointer-events-none' : ''"
                                >
                                    <i class="fas" :class="isUploadingTranscript ? 'fa-spinner fa-spin' : 'fa-file-upload'"></i>
                                    <span x-text="isUploadingTranscript ? 'Uploading Transcript...' : 'Upload Transcript'"></span>
                                </button>

                                <button
                                    type="button"
                                    @click="document.getElementById('minutesUploadInput').click()"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-50 border border-gray-200 text-gray-700 rounded-xl text-sm font-bold hover:bg-white hover:border-[#1d54e2] hover:text-[#1d54e2] transition-all shadow-sm"
                                    :class="isUploadingMinutes ? 'opacity-50 cursor-wait pointer-events-none' : ''"
                                >
                                    <i class="fas" :class="isUploadingMinutes ? 'fa-spinner fa-spin' : 'fa-file-alt'"></i>
                                    <span x-text="isUploadingMinutes ? 'Uploading Minutes...' : 'Upload Minutes'"></span>
                                </button>
                            </div>
                            <p class="text-[10px] text-gray-400 text-center mt-2 italic">AI transcript is not enabled yet. Upload transcript/minutes file manually.</p>
                        </div>
                        </div>
                    </div>

                    <!-- Hidden File Input for Upload -->
                    <input type="file" 
                           id="videoUploadInput"
                           x-ref="videoUploadInput" 
                           class="hidden" 
                           accept="video/*" 
                           @change="uploadVideo($event, selectedMeetingDetails.id)">

                    <input type="file"
                           id="transcriptUploadInput"
                           class="hidden"
                           accept=".pdf,.doc,.docx,.txt"
                           @change="uploadTranscriptFile($event, selectedMeetingDetails.id)">

                    <input type="file"
                           id="minutesUploadInput"
                           class="hidden"
                           accept=".pdf,.doc,.docx,.txt"
                           @change="uploadMinutesFile($event, selectedMeetingDetails.id)">


                <!-- Notes Tab -->
                <div x-show="meetingDetailsTab === 'notes'" style="display: none;">
                    <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 mb-4">
                        <textarea x-model="newNoteContent" class="w-full bg-transparent resize-none outline-none text-sm text-gray-900 placeholder-gray-400" rows="3" placeholder="Add a note..."></textarea>
                        <div class="flex justify-end mt-2">
                            <button @click="saveNote(selectedMeetingDetails, 'meeting')" class="px-4 py-1.5 bg-[#1d54e2] text-white text-xs font-semibold rounded-lg hover:bg-[#1541b0] transition" x-text="editingNote ? 'Update Note' : 'Save Note'"></button>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <template x-for="note in selectedMeetingDetails?.notes || []" :key="note.id">
                            <div class="flex gap-3">
                                <div class="w-8 h-8 rounded-full bg-[#1d54e2] text-white flex items-center justify-center text-xs font-bold shrink-0" x-text="(note.owner || 'JK').split(' ').map(n => n[0]).join('').toUpperCase()">
                                </div>
                                <div class="flex-1 text-sm bg-white p-3 rounded-lg border border-gray-100 shadow-sm relative group">
                                    <div class="flex justify-between items-start mb-1">
                                        <span class="font-semibold text-gray-900" x-text="note.owner"></span>
                                        <div class="flex items-center gap-2">
                                            <div class="opacity-0 group-hover:opacity-100 transition-opacity flex gap-2">
                                                <i @click="editNote(note)" class="fas fa-pencil-alt text-[10px] text-gray-400 hover:text-blue-600 cursor-pointer"></i>
                                                <i @click="deleteNote(note.id, selectedMeetingDetails)" class="far fa-trash-alt text-[10px] text-gray-400 hover:text-red-500 cursor-pointer"></i>
                                            </div>
                                            <span class="text-xs text-gray-500" x-text="new Date(note.created_at).toLocaleDateString() === new Date().toLocaleDateString() ? 'Today' : new Date(note.created_at).toLocaleDateString()"></span>
                                        </div>
                                    </div>
                                    <p class="text-gray-700 whitespace-pre-line" x-text="note.content"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div>

    <!-- Video Player Slide-over -->
    <div x-show="showVideoPlayer" 
         x-data="videoPlayer()"
         @keydown.escape.window="showVideoPlayer = false"
         style="display: none;" 
         class="fixed top-0 bottom-0 right-0 z-[110] w-full max-w-[500px] transition-all"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-8"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-8">
         
        <div class="bg-white shadow-2xl w-full h-full flex flex-col overflow-hidden border-l border-gray-200">
            
            <!-- Header -->
            <div class="px-5 py-4 flex items-center justify-between shrink-0 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Meeting Recording</h3>
                <button @click="showVideoPlayer = false" class="text-gray-400 hover:text-gray-600 transition text-lg leading-none">&times;</button>
            </div>
            
            <!-- Video Area -->
            <div class="flex-1 bg-gray-50 flex flex-col p-6">
                <div class="bg-black rounded-xl flex items-center justify-center overflow-hidden aspect-video shadow-lg mb-6 relative group">
                    <video x-ref="videoElement" 
                           :src="videoSrc" 
                           @timeupdate="updateTime()" 
                           @loadedmetadata="updateTime()" 
                           @ended="playing = false"
                           class="w-full h-full object-contain cursor-pointer"
                           @click="togglePlay()"></video>
                    
                    <!-- Overlay when paused -->
                    <div x-show="!playing && currentTime === 0" class="absolute inset-0 flex flex-col items-center justify-center text-gray-500 bg-black/50 pointer-events-none transition-opacity">
                        <i class="far fa-file-video text-7xl mb-4 text-white/70"></i>
                        <p class="text-xs font-medium uppercase tracking-widest text-white/90">Video Stream Ready</p>
                    </div>
                </div>

                <!-- Playback Controls (Standardized) -->
                <div class="bg-[#1d54e2] rounded-xl px-6 py-4 flex flex-col shadow-md">
                    <div class="flex items-center gap-4 mb-3">
                        <button @click="togglePlay()" class="text-white hover:text-gray-200 transition">
                            <i :class="['fas text-base', playing ? 'fa-pause' : 'fa-play']"></i>
                        </button>
                        <div class="flex-1 h-1.5 bg-blue-400/30 rounded-full cursor-pointer relative" @click="seek($event)">
                            <div class="absolute left-0 top-0 h-full bg-white rounded-full transition-all duration-75" :style="`width: ${duration ? (currentTime / duration) * 100 : 0}%`"></div>
                        </div>
                        <span class="text-white text-xs font-bold font-mono" x-text="`${formatTime(currentTime)} / ${formatTime(duration)}`">00:00 / 00:00</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <button @click="skip(-10)" class="text-blue-100 hover:text-white transition" title="Rewind 10s"><i class="fas fa-undo text-sm"></i></button>
                            <button @click="skip(10)" class="text-blue-100 hover:text-white transition" title="Skip 10s"><i class="fas fa-redo text-sm"></i></button>
                        </div>
                        <div class="flex items-center gap-4">
                            <button @click="toggleMute()" class="text-blue-100 hover:text-white transition" title="Toggle Mute">
                                <i :class="['fas text-sm', muted ? 'fa-volume-mute text-blue-200' : 'fa-volume-up']"></i>
                            </button>
                            <button @click="toggleFullScreen()" class="text-blue-100 hover:text-white transition" title="Fullscreen">
                                <i class="fas fa-expand text-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-8 space-y-4">
                    <h4 class="text-sm font-bold text-gray-900 border-l-4 border-[#1d54e2] pl-3">Recording Details</h4>
                    <div class="grid grid-cols-1 gap-4">
                        <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                            <p class="text-[10px] text-gray-400 uppercase font-black mb-1">Duration</p>
                            <p class="text-sm font-bold text-gray-700" x-text="formatDurationText(duration)">45 Minutes</p>
                        </div>
                    </div>

                    {{-- Upload Minutes Action --}}
                    <div class="mt-4">
                        <input type="file" x-ref="minutesInputPlayer" @change="uploadMinutes(selectedMeetingDetails.id, $event)" class="hidden" accept=".pdf,.doc,.docx,.txt" />
                        <button @click="$refs.minutesInputPlayer.click()" 
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-50 border border-gray-200 text-gray-700 rounded-xl text-xs font-bold hover:bg-white hover:border-[#1d54e2] hover:text-[#1d54e2] transition shadow-sm group">
                            <i class="fas fa-file-upload text-blue-500 group-hover:scale-110 transition-transform"></i>
                            Upload Meeting Minutes
                        </button>
                        <div x-show="isUploadingMinutes" class="mt-2 px-1">
                            <div class="w-full bg-gray-200 rounded-full h-1 overflow-hidden">
                                <div class="bg-blue-600 h-1 animate-pulse"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <!-- Footer -->
            <div class="px-5 py-4 flex items-center justify-between border-t border-gray-100 shrink-0">
                <div class="flex items-center gap-2">
                    <!-- Transcript Dropdown -->
                    <template x-if="selectedMeetingDetails?.has_transcript">
                        <div class="relative">
                            <button @click="activeDownloadMenu = activeDownloadMenu === 'player_transcript' ? null : 'player_transcript'" 
                                    class="px-3 py-2 rounded-lg border border-gray-200 text-gray-700 bg-gray-50 hover:bg-white hover:border-green-500 hover:text-green-600 text-xs font-bold transition-all shadow-sm flex items-center gap-2">
                                <i class="fas fa-file-alt text-green-500"></i>
                                Transcript
                            </button>
                            <div x-show="activeDownloadMenu === 'player_transcript'" 
                                 @click.away="activeDownloadMenu = null"
                                 class="absolute bottom-full left-0 mb-2 w-32 bg-white rounded-lg shadow-xl border border-gray-100 z-[120] overflow-hidden"
                                 style="display: none;">
                                <button @click="downloadFile('transcript', 'pdf'); activeDownloadMenu = null" class="w-full text-left px-4 py-2 text-[10px] text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                    <i class="far fa-file-pdf text-red-500"></i> PDF (.pdf)
                                </button>
                                <button @click="downloadFile('transcript', 'docs'); activeDownloadMenu = null" class="w-full text-left px-4 py-2 text-[10px] text-gray-700 hover:bg-gray-50 flex items-center gap-2 border-t border-gray-50">
                                    <i class="far fa-file-word text-blue-500"></i> DOCS (.docx)
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Minutes Dropdown -->
                    <template x-if="selectedMeetingDetails?.has_minutes">
                        <div class="relative">
                            <button @click="activeDownloadMenu = activeDownloadMenu === 'player_minutes' ? null : 'player_minutes'" 
                                    class="px-3 py-2 rounded-lg border border-gray-200 text-gray-700 bg-gray-50 hover:bg-white hover:border-purple-500 hover:text-purple-600 text-xs font-bold transition-all shadow-sm flex items-center gap-2">
                                <i class="fas fa-file-signature text-purple-500"></i>
                                Minutes
                            </button>
                            <div x-show="activeDownloadMenu === 'player_minutes'" 
                                 @click.away="activeDownloadMenu = null"
                                 class="absolute bottom-full left-0 mb-2 w-32 bg-white rounded-lg shadow-xl border border-gray-100 z-[120] overflow-hidden"
                                 style="display: none;">
                                <button @click="downloadFile('minutes', 'pdf'); activeDownloadMenu = null" class="w-full text-left px-4 py-2 text-[10px] text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                    <i class="far fa-file-pdf text-red-500"></i> PDF (.pdf)
                                </button>
                                <button @click="downloadFile('minutes', 'docs'); activeDownloadMenu = null" class="w-full text-left px-4 py-2 text-[10px] text-gray-700 hover:bg-gray-50 flex items-center gap-2 border-t border-gray-50">
                                    <i class="far fa-file-word text-blue-500"></i> DOCS (.docx)
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
                <button @click="showVideoPlayer = false" class="px-6 py-2 rounded-md bg-[#1d54e2] text-white hover:bg-[#1541b0] text-sm font-semibold transition shadow-sm">
                    Close
                </button>
            </div>
        </div>
    </div>

    <div x-show="showCallModal" 
         style="display: none;" 
         class="fixed top-0 bottom-0 right-0 z-[100] w-full max-w-[420px] transition-all"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-8"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-8">
         
        <!-- Modal Panel -->
        <div class="bg-white shadow-2xl w-full h-full flex flex-col overflow-hidden border-l border-gray-200">
            
            <!-- Header -->
            <div class="px-5 py-4 flex items-center justify-between shrink-0 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Create Call</h3>
                <button @click="showCallModal = false" class="text-gray-400 hover:text-gray-600 transition text-lg leading-none">&times;</button>
            </div>

            <!-- Body -->
            <div class="px-5 py-4 space-y-4 overflow-y-auto custom-scrollbar flex-1">
                    
                    
                <!-- Related Contact -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Related Contact</label>
                    <p class="text-[11px] text-gray-400 mb-1">Select the contact/company first. For contacts, Client / Contact will auto-fill.</p>
                    
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Related Contact</label>
                    <div class="relative" @click.away="closeRelatedPicker('calls')">
                        <div class="flex flex-wrap gap-1.5 p-2 bg-white border border-gray-300 rounded-md focus-within:border-[#1d54e2] focus-within:ring-1 focus-within:ring-[#1d54e2] transition min-h-[40px]">
                            <template x-for="tag in newCall.relatedTo" :key="tag">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-medium border border-blue-200">
                                    <span x-text="tag"></span>
                                    <i @click="removeTag('calls', tag)" class="fas fa-times cursor-pointer hover:text-blue-800"></i>
                                </span>
                            </template>
                            <input 
                                type="text" 
                                x-model="relatedSearch.calls" 
                                @focus="openRelatedPicker('calls')"
                                @input="openRelatedPicker('calls')"
                                @keydown.enter.prevent="selectFirstRelatedOption('calls')"
                                placeholder="Search Contacts/Companies..." 
                                class="flex-1 min-w-[150px] border-none p-0 text-sm text-gray-800 outline-none focus:ring-0 bg-transparent"
                            />
                        </div>
                        <div x-show="relatedPickerOpen.calls" x-transition class="absolute left-0 right-0 top-full z-[120] mt-2 overflow-hidden rounded-xl border border-blue-100 bg-white shadow-2xl" style="display:none;">
                            <div class="border-b border-blue-50 bg-gradient-to-r from-blue-50 to-white px-4 py-2">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-blue-700">Select Related Record</p>
                            </div>
                            <div class="max-h-64 overflow-y-auto py-2">
                                <template x-for="option in filteredRelatedOptions('calls')" :key="option.key">
                                    <button type="button" @click="selectRelatedOption('calls', option)" class="flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-blue-50">
                                        <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold" :class="option.type === 'company' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'">
                                            <i class="fas" :class="option.type === 'company' ? 'fa-building' : 'fa-user'"></i>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-semibold text-gray-900" x-text="option.label"></span>
                                            <span class="block truncate text-xs text-gray-500" x-text="option.meta || (option.type === 'company' ? 'Company record' : 'Contact record')"></span>
                                        </span>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide" :class="option.type === 'company' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700'" x-text="option.type"></span>
                                    </button>
                                </template>
                                <div x-show="!filteredRelatedOptions('calls').length" class="px-4 py-6 text-center text-sm text-gray-500">No matching contacts or companies found.</div>
                            </div>
                        </div>
                    </div>
                </div>

                
                <!-- Client / Contact -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Client / Contact <span class="text-red-500">*</span></label>
                    <input type="text" x-model="newCall.to" placeholder="Auto-filled from Related Contact, or type manually" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition" />
                </div>

                <!-- Handled By -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Handled By</label>
                    <select
                        x-model="newCall.from"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition"
                    >
                        <option value="">Select user</option>
                        <template x-for="user in systemUsers" :key="user">
                            <option :value="user" x-text="user"></option>
                        </template>
                        <option value="__manual__">Manual entry</option>
                    </select>

                    <div x-show="newCall.from === '__manual__'" class="mt-2" style="display: none;">
                        <input
                            type="text"
                            x-model="newCall.manualFrom"
                            placeholder="Type handler name or email"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition"
                        >
                    </div>
                </div>

<!-- Call Type Selector -->
                <div class="relative" x-data="{ typeOpen: false }">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Call Type</label>
                    <button @click="typeOpen = !typeOpen" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 flex items-center justify-between bg-white hover:border-[#1d54e2] transition outline-none">
                        <span x-text="newCall.type"></span>
                        <i class="fas fa-chevron-down text-[10px] text-gray-400 transition-transform" :class="typeOpen ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="typeOpen" @click.away="typeOpen = false" style="display:none"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg z-[110] py-1 overflow-hidden">
                        <template x-for="t in ['Inbound', 'Outbound']" :key="t">
                            <button @click="handleCallDirection(t); typeOpen = false"
                                    class="w-full text-left px-4 py-2 text-sm hover:bg-blue-50 hover:text-[#1d54e2] transition"
                                    :class="newCall.type === t ? 'bg-blue-50 text-[#1d54e2] font-semibold' : 'text-gray-700'"
                                    x-text="t"></button>
                        </template>
                    </div>
                </div>

                <!-- Call Start Time -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Call Start Time</label>
                    <div class="flex gap-2">
                        <input type="date" x-model="newCall.startTime" class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition" />
                        <input type="time" x-model="newCall.startHour" class="w-32 border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition" />
                    </div>
                </div>

                
                <!-- Call Duration -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Call Duration</label>
                    <select
                        x-model="newCall.duration"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition"
                    >
                        <option value="">Not set / Scheduled call</option>
                        <option value="5 mins">5 mins</option>
                        <option value="10 mins">10 mins</option>
                        <option value="15 mins">15 mins</option>
                        <option value="30 mins">30 mins</option>
                        <option value="45 mins">45 mins</option>
                        <option value="1 hr">1 hr</option>
                        <option value="1 hr 30 mins">1 hr 30 mins</option>
                        <option value="2 hrs">2 hrs</option>
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">Leave blank if this is only a scheduled call and not yet completed.</p>
                </div>

<!-- Call Purpose -->
                <div class="relative" x-data="{ purposeOpen: false, selectedPurpose: '-None-' }">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Call Purpose</label>
                    <button @click="purposeOpen = !purposeOpen" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 flex items-center justify-between bg-white hover:border-[#1d54e2] transition outline-none">
                        <span x-text="selectedPurpose"></span>
                        <i class="fas fa-chevron-down text-[10px] text-gray-400 transition-transform" :class="purposeOpen ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="purposeOpen" @click.away="purposeOpen = false" style="display:none"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg z-[110] py-1 overflow-hidden">
                        <template x-for="p in ['-None-', 'Prospecting', 'Administrative', 'Negotiation', 'Demo', 'Project', 'Desk']" :key="p">
                            <button @click="selectedPurpose = p; newCall.purpose = p; purposeOpen = false"
                                    class="w-full text-left px-4 py-2 text-sm hover:bg-blue-50 hover:text-[#1d54e2] transition"
                                    :class="selectedPurpose === p ? 'bg-blue-50 text-[#1d54e2] font-semibold' : 'text-gray-700'"
                                    x-text="p"></button>
                        </template>
                    </div>
                </div>

                <!-- Call Agenda -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Call Agenda</label>
                    <input type="text" x-model="newCall.agenda" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition" />
                </div>

                <!-- Reminder -->
                <div>
                    <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-600">
                        <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-[#1d54e2] focus:ring-[#1d54e2]"> Reminder
                    </label>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-4 flex items-center justify-between border-t border-gray-100 shrink-0">
                <button @click="showCallModal = false" class="px-6 py-2 rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium transition">Cancel</button>
                <button @click="saveCall()" class="px-6 py-2 rounded-md bg-[#1d54e2] text-white hover:bg-[#1541b0] text-sm font-semibold transition shadow-sm">Save</button>
            </div>
        </div>
    </div>

    <div x-show="showEventModal" 
         style="display: none;" 
         class="fixed top-0 bottom-0 right-0 z-[100] w-full max-w-[420px] transition-all"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-8"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-8">
         
        <!-- Modal Panel -->
        <div class="bg-white shadow-2xl w-full h-full flex flex-col overflow-hidden border-l border-gray-200">

            <!-- Header -->
            <div class="px-5 py-4 flex items-center justify-between shrink-0 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Create Event</h3>
                <button @click="showEventModal = false" class="text-gray-400 hover:text-gray-600 transition text-lg leading-none">&times;</button>
            </div>

            <!-- Body -->
            <div class="px-5 py-4 space-y-4 overflow-y-auto custom-scrollbar flex-1">

                <!-- Title -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" x-model="newEvent.title" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition" />
                </div>

                <!-- Host / Owner -->
                <div class="relative" x-data="{ ownerOpen: false }">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Host</label>
                    <button @click="ownerOpen = !ownerOpen" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 flex items-center justify-between bg-white hover:border-[#1d54e2] transition outline-none">
                        <span x-text="newEvent.host || 'Select host'"></span>
                        <i class="fas fa-chevron-down text-[10px] text-gray-400 transition-transform" :class="ownerOpen ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="ownerOpen" @click.away="ownerOpen = false" style="display:none"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg z-[110] py-1 overflow-hidden">
                        <template x-for="owner in systemUsers" :key="owner">
                            <button @click="newEvent.host = owner; ownerOpen = false"
                                    class="w-full text-left px-4 py-2 text-sm hover:bg-blue-50 hover:text-[#1d54e2] transition"
                                    :class="newEvent.host === owner ? 'bg-blue-50 text-[#1d54e2] font-semibold' : 'text-gray-700'"
                                    x-text="owner"></button>
                        </template>
                    </div>
                </div>

                <!-- Date & Time From -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Start Date</label>
                    <div class="flex gap-2">
                        <input type="date" x-model="newEvent.from" class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition" />
                        <input type="time" class="w-32 border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition" />
                    </div>
                </div>

                <!-- Date & Time To -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">End Date</label>
                    <div class="flex gap-2">
                        <input type="date" x-model="newEvent.to" class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition" />
                        <input type="time" class="w-32 border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition" />
                    </div>
                </div>

                <!-- Location -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Location</label>
                    <input type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition" />
                </div>

                <!-- Related To -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Related Contact</label>
                    <div class="relative" @click.away="closeRelatedPicker('events')">
                        <div class="flex flex-wrap gap-1.5 p-2 bg-white border border-gray-300 rounded-md focus-within:border-[#1d54e2] focus-within:ring-1 focus-within:ring-[#1d54e2] transition min-h-[40px]">
                            <template x-for="tag in newEvent.relatedTo" :key="tag">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs font-medium border border-blue-200">
                                    <span x-text="tag"></span>
                                    <i @click="removeTag('events', tag)" class="fas fa-times cursor-pointer hover:text-blue-800"></i>
                                </span>
                            </template>
                            <input 
                                type="text" 
                                x-model="relatedSearch.events" 
                                @focus="openRelatedPicker('events')"
                                @input="openRelatedPicker('events')"
                                @keydown.enter.prevent="selectFirstRelatedOption('events')"
                                placeholder="Search Contacts/Companies..." 
                                class="flex-1 min-w-[150px] border-none p-0 text-sm text-gray-800 outline-none focus:ring-0 bg-transparent"
                            />
                        </div>
                        <div x-show="relatedPickerOpen.events" x-transition class="absolute left-0 right-0 top-full z-[120] mt-2 overflow-hidden rounded-xl border border-blue-100 bg-white shadow-2xl" style="display:none;">
                            <div class="border-b border-blue-50 bg-gradient-to-r from-blue-50 to-white px-4 py-2">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-blue-700">Select Related Record</p>
                            </div>
                            <div class="max-h-64 overflow-y-auto py-2">
                                <template x-for="option in filteredRelatedOptions('events')" :key="option.key">
                                    <button type="button" @click="selectRelatedOption('events', option)" class="flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-blue-50">
                                        <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold" :class="option.type === 'company' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'">
                                            <i class="fas" :class="option.type === 'company' ? 'fa-building' : 'fa-user'"></i>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-semibold text-gray-900" x-text="option.label"></span>
                                            <span class="block truncate text-xs text-gray-500" x-text="option.meta || (option.type === 'company' ? 'Company record' : 'Contact record')"></span>
                                        </span>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide" :class="option.type === 'company' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700'" x-text="option.type"></span>
                                    </button>
                                </template>
                                <div x-show="!filteredRelatedOptions('events').length" class="px-4 py-6 text-center text-sm text-gray-500">No matching contacts or companies found.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Participants -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Participants</label>
                    <input type="text" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition" />
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Description</label>
                    <textarea rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-800 outline-none focus:border-[#1d54e2] focus:ring-1 focus:ring-[#1d54e2] transition resize-none"></textarea>
                </div>

                <!-- Checkboxes -->
                <div class="flex flex-col gap-2">
                    <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-600"><input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-[#1d54e2] focus:ring-[#1d54e2]"> Repeat</label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-600"><input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-[#1d54e2] focus:ring-[#1d54e2]"> Reminder</label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-600"><input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-[#1d54e2] focus:ring-[#1d54e2]"> Online Meeting</label>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-4 flex items-center justify-between border-t border-gray-100 shrink-0">
                <button @click="showEventModal = false" class="px-6 py-2 rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium transition">Cancel</button>
                <button @click="saveEvent()" class="px-6 py-2 rounded-md bg-[#1d54e2] text-white hover:bg-[#1541b0] text-sm font-semibold transition shadow-sm">Save</button>
            </div>
        </div>
    </div>
    <!-- Export Slide-over -->
    <div x-show="showExportModal" 
         style="display: none;" 
         class="fixed top-0 bottom-0 right-0 z-[100] w-full max-w-[420px] transition-all"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-8"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-8">
         
        <div class="bg-white shadow-2xl w-full h-full flex flex-col overflow-hidden border-l border-gray-200">
            
            <!-- Header -->
            <div class="px-5 py-4 flex items-center justify-between shrink-0 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">Export Data</h3>
                <button @click="showExportModal = false" class="text-gray-400 hover:text-gray-600 transition text-lg leading-none">&times;</button>
            </div>
            
            <!-- Body -->
            <div class="px-5 py-4 space-y-6 overflow-y-auto custom-scrollbar flex-1">
                <div class="space-y-1">
                    <p class="text-sm font-bold text-gray-900">Start an Export</p>
                    <p class="text-xs text-gray-500">Choose the fields you would like to include in your export file.</p>
                </div>
                
                <div class="bg-blue-50 rounded-xl p-5 border border-blue-100">
                    <label class="block text-[11px] font-bold text-blue-700 uppercase tracking-wider mb-2">Select Fields</label>
                    <div class="relative">
                        <select x-model="exportFieldSelection" class="w-full appearance-none bg-white border border-blue-200 rounded-lg py-2.5 px-4 text-sm text-gray-800 outline-none focus:ring-2 focus:ring-blue-500 font-semibold cursor-pointer shadow-sm">
                            <option value="view">Fields from the current view</option>
                            <option value="all">All available fields</option>
                        </select>
                        <i class="fas fa-caret-down text-blue-500 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-3">Export Format</p>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 p-3 bg-white border-2 border-blue-500 rounded-lg flex items-center gap-3">
                            <i class="fas fa-file-csv text-blue-500 text-xl"></i>
                            <div>
                                <p class="text-xs font-bold text-gray-800">CSV File</p>
                                <p class="text-[10px] text-gray-400">Comma separated values</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-4 flex items-center justify-between border-t border-gray-100 shrink-0">
                <button @click="showExportModal = false" class="px-6 py-2 rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium transition">
                    Cancel
                </button>
                <button @click="exportData()" class="px-6 py-2 rounded-md bg-[#1d54e2] text-white hover:bg-[#1541b0] text-sm font-semibold transition shadow-sm">
                    Export Now
                </button>
            </div>
        </div>
        </div>
    </div><!-- end main content -->

</div><!-- end root -->

<script>

document.addEventListener('alpine:init', () => {
    Alpine.data('activitiesData', () => ({ 
        showTaskModal: false,
        activeTab: 'task',
        selectAll: false,
        selectedTasks: [],
        tasks: [],
        systemUsers: [],
        relatedOptions: [],
        relatedRecords: [],
        relatedSearch: { tasks: '', events: '', calls: '', meetings: '' },
        relatedPickerOpen: { tasks: false, events: false, calls: false, meetings: false },
        newTask: { name: '', dueDate: '', relatedTo: [], description: '', priority: false, completed: false, owner: '' },
        
        events: [],
        newEvent: { title: '', from: '', to: '', relatedTo: [], host: '' },

        calls: [],
        newCall: { contact: '', to: '', from: '', manualFrom: '', type: 'Outbound', startTime: '', startHour: '', duration: '', relatedTo: [], owner: '', agenda: '', purpose: '', status: '' },

        meetings: [],
        newMeeting: { title: '', owner: '', date: '', time: '', duration: '', durationHour: '0', durationMin: '30', location: '', attendees: [], description: '', hasVideo: false, hasAudio: false, hasTranscript: false, hasMinutes: false },

        filterSelection: {
            task: 'All Tasks',
            events: 'All Events',
            call: 'All Calls'
        },
        selectedTaskDetails: null,
        selectedTaskType: null,
        meetingsSubTab: 'all',
        meetingFilters: {
            videoRecording: false,
            audioRecording: false,
            transcription: false,
            minutes: false
        },
        showMeetingModal: false,
        selectedMeetingDetails: null,
        meetingDetailsTab: 'information',
        showVideoPlayer: false,
        showCallModal: false,
        showEventModal: false,
        newNoteContent: '',
        editingNote: null,
        activeDownloadMenu: null,
        isUploading: false,
        isUploadingMinutes: false,
        isUploadingAudio: false,
        isCallAudioPlaying: false,
        callAudioCurrentTime: 0,
        callAudioDuration: 0,
        currentPage: 1,
        perPage: 50,
        perPageOptions: [10, 20, 30, 40, 50, 100],
        perPageOpen: false,

        // --- Media Recording State ---
        _videoRecorder: null,
        _audioRecorder: null,
        _videoStream: null,
        _audioStream: null,
        recordingVideoActive: false,
        recordingAudioActive: false,
        recordingError: '',
        recordedVideoUrl: null,
        recordedAudioUrl: null,
        _scheduledTimers: {},           // meetingId -> setTimeout ID
        activeRecordingMeetingId: null, // which meeting is currently recording
        // --- Notification prompt (user-gesture gate for getDisplayMedia) ---
        recordingPromptMeeting: null,   // meeting waiting for user to click Start
        // --- Transcription (Web Speech API) ---
        _speechRecognition: null,
        transcribingActive: false,
        liveTranscript: '',
        // --- Minutes ---
        meetingMinutes: '',
        // --- Elapsed timer ---
        recordingElapsed: 0,
        _elapsedTimer: null,

        // ── Core recorder: start capturing (no auto-stop on duration) ──────────
        // capturedStream: a MediaStream from getDisplayMedia, provided by startRecordingForMeeting
        async startRecording(type, capturedStream) {
            try {
                let stream;
                if (type === 'video') {
                    // Full display stream: video + audio from the Google Meet tab
                    stream = capturedStream;
                } else {
                    // Audio-only: extract audio tracks from the display stream
                    const audioTracks = capturedStream.getAudioTracks();
                    stream = audioTracks.length
                        ? new MediaStream(audioTracks)
                        : capturedStream; // fallback: use full stream
                }
                const chunks = [];
                const mimeType = type === 'video'
                    ? (MediaRecorder.isTypeSupported('video/webm;codecs=vp9') ? 'video/webm;codecs=vp9' : 'video/webm')
                    : (MediaRecorder.isTypeSupported('audio/webm;codecs=opus') ? 'audio/webm;codecs=opus' : 'audio/webm');
                const recorder = new MediaRecorder(stream, { mimeType });
                recorder.ondataavailable = (e) => { if (e.data.size > 0) chunks.push(e.data); };
                recorder.onstop = () => {
                    const blob = new Blob(chunks, { type: mimeType });
                    const url = URL.createObjectURL(blob);
                    if (type === 'video') {
                        this.recordedVideoUrl = url;
                        this.recordingVideoActive = false;
                        const m = this.meetings.find(m => m.id === this.activeRecordingMeetingId);
                        if (m) m.video_url = url;
                    } else {
                        this.recordedAudioUrl = url;
                        this.recordingAudioActive = false;
                        const m = this.meetings.find(m => m.id === this.activeRecordingMeetingId);
                        if (m) m.audio_url = url;
                    }
                    stream.getTracks().forEach(t => t.stop());
                };
                recorder.start(1000);
                if (type === 'video') {
                    this._videoRecorder = recorder;
                    this._videoStream = stream;
                    this.recordingVideoActive = true;
                    this.recordedVideoUrl = null;
                } else {
                    this._audioRecorder = recorder;
                    this._audioStream = stream;
                    this.recordingAudioActive = true;
                    this.recordedAudioUrl = null;
                }
                this.recordingError = '';
            } catch (err) {
                this.recordingError = 'Recording error: ' + (err.message || err);
            }
        },

        stopRecording(type) {
            if (type === 'video' && this._videoRecorder && this._videoRecorder.state !== 'inactive') {
                this._videoRecorder.stop();
                this._videoRecorder = null;
            }
            if (type === 'audio' && this._audioRecorder && this._audioRecorder.state !== 'inactive') {
                this._audioRecorder.stop();
                this._audioRecorder = null;
            }
        },

        stopAllRecording() {
            this.stopRecording('video');
            this.stopRecording('audio');
            this.activeRecordingMeetingId = null;
            // Also stop elapsed timer
            if (this._elapsedTimer) { clearInterval(this._elapsedTimer); this._elapsedTimer = null; }
        },

        // ── Scheduler: at meeting time, show a notification prompt instead of
        // calling getDisplayMedia directly (browsers block it from setTimeout)
        scheduleRecording(meeting) {
            if (!meeting || meeting.status !== 'upcoming') return;
            if (!meeting.has_video && !meeting.has_audio && !meeting.has_transcript) return;
            if (!meeting.date || !meeting.time) return;

            this.cancelScheduledRecording(meeting.id);

            const meetingStart = new Date(meeting.date + 'T' + meeting.time);
            if (isNaN(meetingStart.getTime())) return;

            const delay = meetingStart.getTime() - Date.now();

            const trigger = () => {
                delete this._scheduledTimers[meeting.id];
                const live = this.meetings.find(m => m.id === meeting.id);
                if (live && live.status === 'upcoming') {
                    this.promptRecordingForMeeting(live);
                }
            };

            if (delay <= 0) {
                trigger();
            } else {
                const timerId = setTimeout(trigger, Math.min(delay, 2147483647));
                this._scheduledTimers[meeting.id] = timerId;
            }
        },

        cancelScheduledRecording(meetingId) {
            if (this._scheduledTimers[meetingId] !== undefined) {
                clearTimeout(this._scheduledTimers[meetingId]);
                delete this._scheduledTimers[meetingId];
            }
        },

        // ── Show the notification prompt (called by the scheduler) ───────────
        promptRecordingForMeeting(meeting) {
            this.recordingPromptMeeting = meeting;
        },

        dismissRecordingPrompt() {
            this.recordingPromptMeeting = null;
            this.recordingError = '';
        },

        // ── confirmStartRecording: called via user CLICK — valid gesture for getDisplayMedia
        async confirmStartRecording() {
            const meeting = this.recordingPromptMeeting;
            if (!meeting) return;
            this.recordingPromptMeeting = null; // close the prompt immediately

            if (this.activeRecordingMeetingId === meeting.id) return;
            this.stopAllAndFinalize();
            this.activeRecordingMeetingId = meeting.id;
            this.liveTranscript = '';
            this.meetingMinutes = '';
            this.recordingElapsed = 0;

            const needsMedia = meeting.has_video || meeting.has_audio;
            const needsTranscript = meeting.has_transcript || meeting.has_minutes;

            try {
                if (needsMedia) {
                    // User picks the Google Meet tab — valid user-gesture context
                    const displayStream = await navigator.mediaDevices.getDisplayMedia({
                        video: true,
                        audio: true   // tab audio captures Google Meet voices
                    });
                    this.recordingError = '';

                    if (meeting.has_video) await this.startRecording('video', displayStream);
                    if (meeting.has_audio) await this.startRecording('audio', displayStream);

                    // Auto-stop recorders if user clicks browser's Stop Sharing button
                    displayStream.getVideoTracks().forEach(track => {
                        track.addEventListener('ended', () => this.stopAllAndFinalize(), { once: true });
                    });
                }

                if (needsTranscript) {
                    this.startTranscription(meeting);
                }

                // Start elapsed timer
                this._elapsedTimer = setInterval(() => { this.recordingElapsed++; }, 1000);

            } catch (err) {
                this.recordingError = 'Screen capture cancelled or denied: ' + (err.message || err);
                this.activeRecordingMeetingId = null;
            }
        },

        // ── Web Speech API transcription ──────────────────────────────────────
        startTranscription(meeting) {
            const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SR) {
                // Browser doesn't support Speech Recognition — skip silently
                return;
            }
            const recognition = new SR();
            recognition.continuous = true;
            recognition.interimResults = false;
            recognition.lang = 'en-US';
            recognition.onresult = (event) => {
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    if (event.results[i].isFinal) {
                        this.liveTranscript += event.results[i][0].transcript + ' ';
                    }
                }
            };
            recognition.onerror = () => { /* ignore errors, keep recording */ };
            recognition.onend = () => {
                if (this.transcribingActive) recognition.start(); // auto-restart
            };
            recognition.start();
            this._speechRecognition = recognition;
            this.transcribingActive = true;
        },

        stopTranscription() {
            if (this._speechRecognition) {
                this.transcribingActive = false; // prevent auto-restart
                this._speechRecognition.stop();
                this._speechRecognition = null;
            }
        },

        // ── Auto-generate meeting minutes from transcript ─────────────────────
        generateMinutes(meeting) {
            if (!this.liveTranscript.trim()) return;
            const now = new Date();
            const dateStr = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            this.meetingMinutes =
                `MEETING MINUTES\n` +
                `================\n` +
                `Meeting: ${meeting.title}\n` +
                `Date: ${dateStr}\n` +
                `Time: ${meeting.time || timeStr}\n` +
                `Duration: ${Math.floor(this.recordingElapsed / 60)} min ${this.recordingElapsed % 60} sec\n` +
                `Attendees: ${meeting.attendees || 'N/A'}\n\n` +
                `TRANSCRIPT\n` +
                `----------\n` +
                this.liveTranscript.trim() + '\n\n' +
                `[Generated automatically at ${timeStr}]`;
            // Attach to the meeting object for display in the details panel
            const live = this.meetings.find(m => m.id === meeting.id);
            if (live) {
                live.transcript_text = this.liveTranscript.trim();
                live.minutes_text = this.meetingMinutes;
            }
        },

        // ── Stop everything and finalise ──────────────────────────────────────
        stopAllAndFinalize() {
            const meetingId = this.activeRecordingMeetingId;
            const meeting = meetingId ? this.meetings.find(m => m.id === meetingId) : null;
            this.stopTranscription();
            this.stopAllRecording();
            if (meeting) this.generateMinutes(meeting);
            this.recordingElapsed = 0;
        },

        async startRecordingForMeeting(meeting) {
            // Legacy method — now delegates to the prompt approach
            this.promptRecordingForMeeting(meeting);
        },
        
        async uploadVideo(event, meetingId) {
            const file = event.target.files[0];
            if (!file) return;

            this.isUploading = true;
            try {
                const chunkSize = 1024 * 1024; // 1MB chunks
                const totalChunks = Math.ceil(file.size / chunkSize);
                let updatedMeeting = null;
                const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');

                for (let i = 0; i < totalChunks; i++) {
                    const chunk = file.slice(i * chunkSize, (i + 1) * chunkSize);
                    const formData = new FormData();
                    formData.append('video', chunk);
                    formData.append('chunkIndex', i);
                    formData.append('totalChunks', totalChunks);
                    formData.append('fileName', file.name);

                    const response = await fetch(`/api/meetings/${meetingId}/upload-video`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: formData
                    });

                    if (!response.ok) {
                        const errData = await response.json().catch(() => ({}));
                        throw new Error(errData.message || errData.error || 'Server returned ' + response.status);
                    }
                    
                    if (i === totalChunks - 1) {
                        updatedMeeting = await response.json();
                    }
                }

                if (updatedMeeting) {
                    // Update the meetings array
                    const index = this.meetings.findIndex(m => m.id === meetingId);
                    if (index !== -1) {
                        this.meetings[index] = updatedMeeting;
                    }
                    if (this.selectedMeetingDetails && this.selectedMeetingDetails.id === meetingId) {
                        this.selectedMeetingDetails = updatedMeeting;
                    }
                }
            } catch (error) {
                console.error('Error uploading video:', error);
                alert('Upload failed: ' + error.message);
            } finally {
                this.isUploading = false;
                event.target.value = ''; // Reset input
            }
        },

        async uploadMinutes(meetingId, event) {
            const file = event.target.files[0];
            if (!file) return;

            this.isUploadingMinutes = true;
            const formData = new FormData();
            formData.append('minutes', file);

            try {
                const response = await fetch(`/api/meetings/${meetingId}/upload-minutes`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                    },
                    body: formData
                });
                if (response.ok) {
                    const updatedMeeting = await response.json();
                    const index = this.meetings.findIndex(m => m.id === meetingId);
                    if (index !== -1) {
                        this.meetings[index] = updatedMeeting;
                    }
                    if (this.selectedMeetingDetails && this.selectedMeetingDetails.id === meetingId) {
                        this.selectedMeetingDetails = updatedMeeting;
                    }
                }
            } catch (error) {
                console.error('Error uploading minutes:', error);
                alert('Upload failed: ' + error.message);
            } finally {
                this.isUploadingMinutes = false;
                event.target.value = '';
            }
        },

        formatTimeGeneral(seconds) {
            if (!seconds || isNaN(seconds)) return '00:00';
            let m = Math.floor(seconds / 60);
            let s = Math.floor(seconds % 60);
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        },

        toggleCallAudio() {
            let aud = this.$refs.callAudioPlayer;
            if (!aud) return;
            if (aud.paused) {
                aud.play();
                this.isCallAudioPlaying = true;
            } else {
                aud.pause();
                this.isCallAudioPlaying = false;
            }
        },

        updateCallAudioTime() {
            let aud = this.$refs.callAudioPlayer;
            if (!aud) return;
            this.callAudioCurrentTime = aud.currentTime;
            if (aud.duration && !isNaN(aud.duration)) {
                this.callAudioDuration = aud.duration;
            }
        },

        seekCallAudio(event) {
            let aud = this.$refs.callAudioPlayer;
            if (!aud) return;
            let rect = event.currentTarget.getBoundingClientRect();
            let pos = (event.clientX - rect.left) / rect.width;
            aud.currentTime = pos * this.callAudioDuration;
        },

        async deleteCallAudio(callId) {
            if (!callId) return;
            if (!confirm('Are you sure you want to remove this audio recording?')) return;
            
            try {
                const response = await fetch(`/api/calls/${callId}/audio`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                    }
                });

                if (response.ok) {
                    const updatedCall = await response.json();
                    const index = this.calls.findIndex(c => c.id === callId);
                    if (index !== -1) {
                        this.calls[index] = updatedCall;
                    }
                    if (this.selectedTaskDetails && this.selectedTaskDetails.id === callId) {
                        this.selectedTaskDetails = updatedCall;
                        this.isCallAudioPlaying = false;
                        this.callAudioCurrentTime = 0;
                        if (this.$refs.callAudioPlayer) {
                            this.$refs.callAudioPlayer.pause();
                            this.$refs.callAudioPlayer.load();
                        }
                    }
                }
            } catch (error) {
                console.error('Error removing call audio:', error);
            }
        },

        async uploadCallAudio(event, callId) {
            if (!callId) return;
            const file = event.target.files[0];
            if (!file) return;

            this.isUploadingAudio = true;
            try {
                const chunkSize = 1024 * 1024; // 1MB chunks
                const totalChunks = Math.ceil(file.size / chunkSize);
                let updatedCall = null;
                const csrfToken = document.querySelector('meta[name=csrf-token]').getAttribute('content');

                for (let i = 0; i < totalChunks; i++) {
                    const chunk = file.slice(i * chunkSize, (i + 1) * chunkSize);
                    const formData = new FormData();
                    formData.append('audio', chunk);
                    formData.append('chunkIndex', i);
                    formData.append('totalChunks', totalChunks);
                    formData.append('fileName', file.name);

                    const response = await fetch(`/api/calls/${callId}/upload-audio`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: formData
                    });

                    if (!response.ok) {
                        const errData = await response.json().catch(() => ({}));
                        throw new Error(errData.message || errData.error || 'Server returned ' + response.status);
                    }
                    
                    if (i === totalChunks - 1) {
                        updatedCall = await response.json();
                    }
                }

                if (updatedCall) {
                    const index = this.calls.findIndex(c => c.id === callId);
                    if (index !== -1) {
                        this.calls[index] = updatedCall;
                    }

                    if (this.selectedTaskDetails && this.selectedTaskDetails.id === callId) {
                        this.selectedTaskDetails = updatedCall;
                    }
                }
            } catch (error) {
                alert('Upload failed: ' + error.message);
                console.error('Error uploading call audio:', error);
            } finally {
                this.isUploadingAudio = false;
                event.target.value = '';
            }
        },

        init() {
            this.$watch('activeTab', () => this.currentPage = 1);
            this.$watch('filterSelection.task', () => this.currentPage = 1);
            this.$watch('filterSelection.events', () => this.currentPage = 1);
            this.$watch('filterSelection.call', () => this.currentPage = 1);
            this.$watch('meetingsSubTab', () => this.currentPage = 1);
            this.$watch('perPage', () => this.currentPage = 1);

            // Initial fetch (scheduleRecording is called inside fetchActivities)
            this.fetchActivities().then(() => this.applyContactPrefillFromShortcut());

            // Refresh every 60 s — re-schedule any newly added meetings
            setInterval(() => {
                this.fetchActivities();
            }, 60000);
        },

        async fetchActivities() {
            try {
                const response = await fetch('/api/activities');
                const data = await response.json();
                this.tasks = data.tasks;
                this.events = data.events;
                this.calls = data.calls;
                this.meetings = data.meetings;
                this.relatedOptions = data.relatedOptions || [];
                this.relatedRecords = data.relatedRecords || [];
                this.contacts = data.contacts || [];
                if (data.users) {
                    this.systemUsers = data.users;
                    const defaultUser = this.systemUsers[0] || '';
                    if (!this.newTask.owner) this.newTask.owner = defaultUser;
                    if (!this.newEvent.host) this.newEvent.host = defaultUser;
                    if (!this.newCall.owner) this.newCall.owner = defaultUser;
                    // For new calls, default to Outbound with user as 'From'
                    if (this.newCall && !this.newCall.id && !this.newCall.from && !this.newCall.to) {
                        this.newCall.from = defaultUser;
                        this.newCall.type = 'Outbound';
                    }
                    if (!this.newMeeting.owner) this.newMeeting.owner = defaultUser;
                }
                // Schedule recordings for all upcoming meetings that need it
                if (this.meetings) {
                    this.meetings.forEach(m => {
                        // Only add a timer if none exists yet for this meeting
                        if ((m.has_video || m.has_audio) && this._scheduledTimers[m.id] === undefined
                            && this.activeRecordingMeetingId !== m.id) {
                            this.scheduleRecording(m);
                        }
                    });
                }
            } catch (error) {
                console.error('Error fetching activities:', error);
            }
        },

        async saveTask() {
            if (!this.newTask.name) return;
            try {
                const isEdit = !!this.newTask.id;
                const url = isEdit ? '/api/tasks/' + this.newTask.id : '/api/tasks';
                const method = isEdit ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        name: this.newTask.name,
                        due_date: this.newTask.dueDate,
                        status: this.newTask.completed ? 'Completed' : 'Open',
                        priority: this.newTask.priority ? 'High' : 'Normal',
                        related_to: Array.isArray(this.newTask.relatedTo) ? this.newTask.relatedTo.join(', ') : this.newTask.relatedTo,
                        owner: this.newTask.owner,
                        description: this.newTask.description
                    })
                });
                const task = await response.json();
                if (isEdit) {
                    const index = this.tasks.findIndex(t => t.id === task.id);
                    if (index !== -1) this.tasks[index] = task;
                } else {
                    this.tasks.unshift(task);
                }
                this.showTaskModal = false;
                this.newTask = { id: null, name: '', dueDate: '', relatedTo: [], description: '', priority: false, completed: false, owner: (this.systemUsers[0] || '') };
                this.relatedSearch.tasks = '';
                this.relatedPickerOpen.tasks = false;
            } catch (error) {
                console.error('Error saving task:', error);
            }
        },

        async saveEvent() {
            if (!this.newEvent.title) return;
            try {
                const isEdit = !!this.newEvent.id;
                const url = isEdit ? '/api/events/' + this.newEvent.id : '/api/events';
                const method = isEdit ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        title: this.newEvent.title,
                        from: this.newEvent.from,
                        to: this.newEvent.to,
                        related_to: Array.isArray(this.newEvent.relatedTo) ? this.newEvent.relatedTo.join(', ') : this.newEvent.relatedTo,
                        host: this.newEvent.host
                    })
                });
                const event = await response.json();
                if (isEdit) {
                    const index = this.events.findIndex(e => e.id === event.id);
                    if (index !== -1) this.events[index] = event;
                } else {
                    this.events.unshift(event);
                }
                this.showEventModal = false;
                this.newEvent = { id: null, title: '', from: '', to: '', relatedTo: [], host: (this.systemUsers[0] || '') };
                this.relatedSearch.events = '';
                this.relatedPickerOpen.events = false;
            } catch (error) {
                console.error('Error saving event:', error);
            }
        },

        async saveCall() {
            const handledBy = this.newCall.from === '__manual__'
                ? (this.newCall.manualFrom || '')
                : (this.newCall.from || '');

            if (!this.newCall.to && !handledBy && !this.newCall.contact) return;

            // Build combined contact from client/contact and handled-by fields
            const toVal = this.newCall.to || '';
            const fromVal = handledBy;
            const combinedContact = toVal && fromVal ? toVal + ' / ' + fromVal : (toVal || fromVal || this.newCall.contact);
            try {
                const isEdit = !!this.newCall.id;
                const url = isEdit ? '/api/calls/' + this.newCall.id : '/api/calls';
                const method = isEdit ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        contact: combinedContact,
                        to: this.newCall.to,
                        from: handledBy,
                        type: this.newCall.type,
                        start_time: this.newCall.startTime,
                        start_hour: this.newCall.startHour,
                        duration: this.newCall.duration,
                        related_to: Array.isArray(this.newCall.relatedTo) ? this.newCall.relatedTo.join(', ') : this.newCall.relatedTo,
                        owner: handledBy || this.newCall.owner,
                        purpose: this.newCall.purpose,
                        agenda: this.newCall.agenda,
                        completed: this.newCall.completed || false
                    })
                });
                const call = await response.json();
                if (isEdit) {
                    const index = this.calls.findIndex(c => c.id === call.id);
                    if (index !== -1) this.calls[index] = call;
                } else {
                    this.calls.unshift(call);
                }
                this.showCallModal = false;
                const defaultUser = (this.systemUsers[0] || '');
                this.newCall = { id: null, contact: '', to: '', from: defaultUser, manualFrom: '', type: 'Outbound', startTime: '', startHour: '', duration: '', relatedTo: [], owner: defaultUser, agenda: '', purpose: '', completed: false };
                this.relatedSearch.calls = '';
                this.relatedPickerOpen.calls = false;
            } catch (error) {
                console.error('Error saving call:', error);
            }
        },

        async saveMeeting() {
            if (!this.newMeeting.title) return;
            try {
                // Determine DB-friendly duration string
                let computedDuration = '';
                const h = parseInt(this.newMeeting.durationHour) || 0;
                const m = parseInt(this.newMeeting.durationMin) || 0;
                if (h > 0) computedDuration += h + ' hr ';
                if (m > 0 || h === 0) computedDuration += m + ' mins';
                this.newMeeting.duration = computedDuration.trim();

                const isEdit = !!this.newMeeting.id;
                const url = isEdit ? '/api/meetings/' + this.newMeeting.id : '/api/meetings';
                const method = isEdit ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        title: this.newMeeting.title,
                        owner: this.newMeeting.owner,
                        date: this.newMeeting.date,
                        time: this.newMeeting.time,
                        duration: this.newMeeting.duration,
                        location: this.newMeeting.location,
                        attendees: this.newMeeting.attendees.length,
                        related_to: Array.isArray(this.newMeeting.attendees) ? this.newMeeting.attendees.join(', ') : this.newMeeting.attendees,
                        status: this.newMeeting.status || 'upcoming',
                        description: this.newMeeting.description,
                        has_video: this.newMeeting.hasVideo,
                        has_audio: this.newMeeting.hasAudio,
                        has_transcript: this.newMeeting.hasTranscript,
                        has_minutes: this.newMeeting.hasMinutes
                    })
                });
                const meeting = await response.json();

                // Cancel any stale timer for this meeting and re-schedule with updated date/time
                this.cancelScheduledRecording(meeting.id);

                if (isEdit) {
                    const index = this.meetings.findIndex(m => m.id === meeting.id);
                    if (index !== -1) this.meetings[index] = meeting;
                } else {
                    this.meetings.unshift(meeting);
                }

                // Schedule recording to fire at the meeting's date+time
                this.scheduleRecording(meeting);

                this.showMeetingModal = false;
                this.recordingError = '';
                this.newMeeting = { id: null, title: '', owner: (this.systemUsers[0] || ''), date: '', time: '', duration: '', durationHour: '0', durationMin: '30', location: '', attendees: [], description: '', hasVideo: false, hasAudio: false, hasTranscript: false, hasMinutes: false, status: 'upcoming' };
                this.relatedSearch.meetings = '';
                this.relatedPickerOpen.meetings = false;
            } catch (error) {
                console.error('Error saving meeting:', error);
            }
        },

        editTask(task) {
            this.newTask = { 
                id: task.id, 
                name: task.name, 
                dueDate: task.due_date, 
                relatedTo: task.related_to ? task.related_to.split(', ').filter(Boolean) : [], 
                description: task.description, 
                priority: task.priority === 'High', 
                completed: task.status === 'Completed', 
                owner: task.owner 
            };
            this.relatedSearch.tasks = '';
            this.relatedPickerOpen.tasks = false;
            this.showTaskModal = true;
        },

        editEvent(event) {
            this.newEvent = { 
                id: event.id,
                title: event.title,
                from: event.from,
                to: event.to,
                relatedTo: event.related_to ? event.related_to.split(', ').filter(Boolean) : [],
                host: event.host
            };
            this.relatedSearch.events = '';
            this.relatedPickerOpen.events = false;
            this.showEventModal = true;
        },

        editCall(call) {
            // Parse existing contact back into to/from if it contains ' / '
            let toVal = '', fromVal = '';
            if (call.contact && call.contact.includes(' / ')) {
                const parts = call.contact.split(' / ');
                toVal = parts[0] || '';
                fromVal = parts[1] || '';
            } else {
                toVal = call.contact || '';
            }
            this.newCall = { 
                id: call.id,
                contact: call.contact,
                to: toVal,
                from: fromVal,
                manualFrom: '',
                type: call.type,
                startTime: call.start_time,
                startHour: call.start_hour,
                duration: call.duration,
                relatedTo: call.related_to ? call.related_to.split(', ').filter(Boolean) : [],
                owner: call.owner,
                agenda: call.agenda,
                purpose: call.purpose,
                completed: call.completed
            };
            this.relatedSearch.calls = '';
            this.relatedPickerOpen.calls = false;
            this.showCallModal = true;
        },

        handleCallDirection(type) {
            this.newCall.type = type;
            const currentUser = this.systemUsers[0] || '';
            if (type === 'Inbound') {
                this.newCall.to = currentUser;
                this.newCall.from = '';
            } else {
                this.newCall.from = currentUser;
                this.newCall.to = '';
            }
        },

        openRelatedPicker(type) {
            this.relatedPickerOpen[type] = true;
        },

        closeRelatedPicker(type) {
            this.relatedPickerOpen[type] = false;
        },

        filteredRelatedOptions(type) {
            const query = (this.relatedSearch[type] || '').toLowerCase().trim();
            const selected = this.currentRelatedSelections(type);

            return this.relatedRecords
                .filter((option) => !selected.includes(option.label))
                .filter((option) => {
                    if (!query) return true;
                    const haystack = `${option.label} ${option.meta || ''} ${option.tokens || ''}`.toLowerCase();
                    return haystack.includes(query);
                })
                .slice(0, 8);
        },

        selectFirstRelatedOption(type) {
            const [first] = this.filteredRelatedOptions(type);
            if (first) {
                this.selectRelatedOption(type, first);
            }
        },

        selectRelatedOption(type, option) {
            this.addTag(type, option.label);

            if (type === 'calls' && option) {
                this.syncCallClientFromRelatedContact(option.label, option);
            }

            this.relatedSearch[type] = '';
            this.relatedPickerOpen[type] = false;
        },

        currentRelatedSelections(type) {
            if (type === 'calls') return this.newCall.relatedTo;
            if (type === 'events') return this.newEvent.relatedTo;
            if (type === 'meetings') return this.newMeeting.attendees;

            return this.newTask.relatedTo;
        },

        addTag(type, tag) {
            const value = (tag || '').trim();
            let list;
            if (type === 'calls') list = this.newCall.relatedTo;
            else if (type === 'events') list = this.newEvent.relatedTo;
            else if (type === 'meetings') list = this.newMeeting.attendees;
            else list = this.newTask.relatedTo;

            if (value && !list.includes(value)) {
                list.push(value);
            }

            if (type === 'calls' && value) {
                this.syncCallClientFromRelatedContact(value);
            }
        },

        relatedText(value) {
            return value && String(value).trim() ? value : '-';
        },

        contactNameFromLabel(value) {
            const label = String(value || '').trim();
            if (!label) return '';
            return label.split(' - ')[0].trim() || label;
        },

        syncCallClientFromRelatedContact(label, option = null) {
            if (option && option.type === 'contact' && option.label) {
                this.newCall.to = option.label;
                return;
            }

            const match = this.relatedRecords.find((record) => record.label === label);
            if (match && match.type === 'contact') {
                this.newCall.to = match.label;
                return;
            }

            const clientName = this.contactNameFromLabel(label);
            if (clientName) {
                this.newCall.to = clientName;
            }
        },

        applyContactPrefillFromShortcut() {
            let payload = null;

            try {
                payload = JSON.parse(localStorage.getItem('activity_contact_prefill') || 'null');
            } catch (error) {
                payload = null;
            }

            if (!payload) return;

            localStorage.removeItem('activity_contact_prefill');

            const urlParams = new URLSearchParams(window.location.search);
            const requestedType = urlParams.get('open') || payload.type || 'task';
            const relatedContact = payload.related_contact || payload.contact_name || '';
            const contactName = payload.contact_name || this.contactNameFromLabel(relatedContact);
            const defaultUser = this.systemUsers[0] || '';
            const today = new Date().toISOString().slice(0, 10);

            if (requestedType === 'call') {
                this.activeTab = 'call';
                this.showCallModal = true;
                this.newCall.relatedTo = relatedContact ? [relatedContact] : [];
                this.newCall.to = contactName;
                this.newCall.from = defaultUser;
                this.newCall.owner = defaultUser;
                this.newCall.type = 'Outbound';
                this.newCall.startTime = today;
                this.newCall.startHour = new Date().toTimeString().slice(0, 5);
                return;
            }

            if (requestedType === 'meetings') {
                this.activeTab = 'meetings';
                this.showMeetingModal = true;
                this.newMeeting.attendees = relatedContact ? [relatedContact] : [];
                this.newMeeting.date = today;
                this.newMeeting.time = new Date().toTimeString().slice(0, 5);
                return;
            }

            this.activeTab = 'task';
            this.showTaskModal = true;
            this.newTask.relatedTo = relatedContact ? [relatedContact] : [];
            this.newTask.name = contactName ? `Follow up with ${contactName}` : '';
        },

        removeTag(type, tag) {
            if (type === 'calls') {
                this.newCall.relatedTo = this.newCall.relatedTo.filter(t => t !== tag);
            } else if (type === 'events') {
                this.newEvent.relatedTo = this.newEvent.relatedTo.filter(t => t !== tag);
            } else if (type === 'meetings') {
                this.newMeeting.attendees = this.newMeeting.attendees.filter(t => t !== tag);
            } else {
                this.newTask.relatedTo = this.newTask.relatedTo.filter(t => t !== tag);
            }
        },

        editMeeting(meeting) {
            let parsedHr = '0';
            let parsedMin = '30';
            if (meeting.duration) {
                const hrMatch = meeting.duration.match(/(\d+)\s*hr/i);
                if (hrMatch) parsedHr = hrMatch[1];
                const minMatch = meeting.duration.match(/(\d+)\s*min/i);
                if (minMatch) parsedMin = minMatch[1];
            }

            this.newMeeting = { 
                id: meeting.id,
                title: meeting.title,
                owner: meeting.owner,
                date: meeting.date,
                time: meeting.time,
                duration: meeting.duration,
                durationHour: parsedHr,
                durationMin: parsedMin,
                location: meeting.location,
                attendees: meeting.related_to ? meeting.related_to.split(', ').filter(Boolean) : [],
                description: meeting.description,
                hasVideo: meeting.has_video,
                hasAudio: meeting.has_audio,
                hasTranscript: meeting.has_transcript,
                hasMinutes: meeting.has_minutes,
                status: meeting.status
            };
            this.relatedSearch.meetings = '';
            this.relatedPickerOpen.meetings = false;
            this.showMeetingModal = true;
        },

        get taskCounts() {
            return {
                total: this.tasks.length,
                open: this.tasks.filter(t => t.status === 'Open').length,
                completed: this.tasks.filter(t => t.status === 'Completed').length,
                overdue: this.tasks.filter(t => {
                    if (t.status === 'Completed') return false;
                    if (!t.due_date) return false;
                    return new Date(t.due_date) < new Date();
                }).length
            };
        },

        get eventCounts() {
            return {
                total: this.events.length,
                upcoming: this.events.filter(e => {
                    if (!e.from) return false;
                    return new Date(e.from) > new Date();
                }).length,
                closed: this.events.filter(e => {
                    if (!e.to) return false;
                    return new Date(e.to) < new Date();
                }).length
            };
        },

        get meetingCounts() {
            return {
                total: this.meetings.length,
                upcoming: this.meetings.filter(m => m.status === 'upcoming').length,
                completed: this.meetings.filter(m => m.status === 'completed').length
            };
        },

        get callCounts() {
            return {
                total: this.calls.length,
                scheduled: this.calls.filter(c => !c.completed).length,
                overdue: this.calls.filter(c => !c.completed && c.start_time && new Date(c.start_time) < new Date()).length
            };
        },

        get filteredTasksList() {
            let list = this.tasks;
            if (this.filterSelection.task === 'Open Tasks') list = list.filter(t => t.status === 'Open');
            if (this.filterSelection.task === 'Closed Tasks') list = list.filter(t => t.status === 'Completed');
            if (this.filterSelection.task === 'Overdue Tasks') list = list.filter(t => t.status !== 'Completed' && t.due_date && new Date(t.due_date) < new Date());
            return list;
        },
        get filteredEventsList() {
            let list = this.events;
            if (this.filterSelection.events === 'Upcoming Events') list = list.filter(e => e.from && new Date(e.from) > new Date());
            if (this.filterSelection.events === 'Closed Events') list = list.filter(e => e.to && new Date(e.to) < new Date());
            return list;
        },
        get filteredCallsList() {
            let list = this.calls;
            if (this.filterSelection.call === 'Scheduled Calls') list = list.filter(c => !c.completed);
            if (this.filterSelection.call === 'Overdue Calls') list = list.filter(c => !c.completed && c.start_time && new Date(c.start_time) < new Date());
            if (this.filterSelection.call === 'Inbound Calls') list = list.filter(c => c.type === 'Inbound');
            if (this.filterSelection.call === 'Outbound Calls') list = list.filter(c => c.type === 'Outbound');
            return list;
        },
        get activeList() {
            if (this.activeTab === 'task') return this.filteredTasksList;
            if (this.activeTab === 'events') return this.filteredEventsList;
            if (this.activeTab === 'call') return this.filteredCallsList;
            if (this.activeTab === 'meetings') return this.filteredMeetings;
            return [];
        },

        async deleteActivity(id, type) {
            if (!confirm('Are you sure you want to delete this ' + type.slice(0, -1) + '?')) return;
            try {
                const response = await fetch('/api/' + type + '/' + id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                    }
                });
                if (response.ok) {
                    // If deleting a meeting, cancel any pending recording timer
                    if (type === 'meetings') {
                        this.cancelScheduledRecording(id);
                        if (this.activeRecordingMeetingId === id) this.stopAllRecording();
                    }
                    this[type] = this[type].filter(item => item.id !== id);
                    if (type === 'tasks' && this.selectedTaskDetails?.id === id) this.selectedTaskDetails = null;
                    if (type === 'meetings' && this.selectedMeetingDetails?.id === id) this.selectedMeetingDetails = null;
                }
            } catch (error) {
                console.error('Error deleting ' + type + ':', error);
            }
        },

        get filteredMeetings() {
            return this.meetings.filter(m => {
                if (this.meetingsSubTab === 'upcoming' && m.status !== 'upcoming') return false;
                if (this.meetingsSubTab === 'completed' && m.status !== 'completed') return false;
                
                if (this.meetingFilters.videoRecording && !m.has_video) return false;
                if (this.meetingFilters.audioRecording && !m.has_audio) return false;
                if (this.meetingFilters.transcription && !m.has_transcript) return false;
                if (this.meetingFilters.minutes && !m.has_minutes) return false;
                
                return true;
            });
        },

        showExportModal: false,
        exportFieldSelection: 'view',

        exportData() {
            let dataToExport = [];
            let csvContent = '';
            let filename = 'export.csv';
            const q = String.fromCharCode(34);
            
            if (this.activeTab === 'task') {
                dataToExport = this.tasks;
                filename = 'tasks_export.csv';
                if (this.exportFieldSelection === 'view') {
                    csvContent += 'Task Name,Due Date,Status,Priority,Related To,Owner\n';
                    dataToExport.forEach(item => {
                        csvContent += [item.name, item.due_date, item.status, item.priority, item.related_to, item.owner].map(v => q + (v || '') + q).join(',') + '\n';
                    });
                } else {
                    csvContent += 'Task Name,Due Date,Status,Priority,Related To,Owner,Description\n';
                    dataToExport.forEach(item => {
                        csvContent += [item.name, item.due_date, item.status, item.priority, item.related_to, item.owner, item.description].map(v => q + (v || '') + q).join(',') + '\n';
                    });
                }
            } else if (this.activeTab === 'events') {
                dataToExport = this.events;
                filename = 'events_export.csv';
                if (this.exportFieldSelection === 'view') {
                    csvContent += 'Title,From,To,Related To,Host\n';
                    dataToExport.forEach(item => {
                        csvContent += [item.title, item.from, item.to, item.related_to, item.host].map(v => q + (v || '') + q).join(',') + '\n';
                    });
                } else {
                     csvContent += 'Title,From,To,Related To,Host\n';
                     dataToExport.forEach(item => {
                        csvContent += [item.title, item.from, item.to, item.related_to, item.host].map(v => q + (v || '') + q).join(',') + '\n';
                    });
                }
            } else if (this.activeTab === 'call') {
                dataToExport = this.calls;
                filename = 'calls_export.csv';
                if (this.exportFieldSelection === 'view') {
                    csvContent += 'Contact,Type,Start Time,Start Hour,Duration,Related To,Owner\n';
                    dataToExport.forEach(item => {
                        csvContent += [item.contact, item.type, item.start_time, item.start_hour, item.duration, item.related_to, item.owner].map(v => q + (v || '') + q).join(',') + '\n';
                    });
                } else {
                     csvContent += 'Contact,Type,Start Time,Start Hour,Duration,Related To,Owner,Purpose,Agenda,Completed\n';
                     dataToExport.forEach(item => {
                        const completedStr = item.completed ? 'Yes' : 'No';
                        csvContent += [item.contact, item.type, item.start_time, item.start_hour, item.duration, item.related_to, item.owner, item.purpose, item.agenda, completedStr].map(v => q + (v || '') + q).join(',') + '\n';
                    });
                }
            }

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
            this.showExportModal = false;
        },

        async saveNote(noteable, type) {
            if (!this.newNoteContent) return;
            try {
                const isEdit = !!this.editingNote;
                const url = isEdit ? '/api/notes/' + this.editingNote.id : '/api/notes';
                const method = isEdit ? 'PUT' : 'POST';

                const body = isEdit ? { content: this.newNoteContent } : {
                    content: this.newNoteContent,
                    owner: (this.systemUsers[0] || 'John Kelly'),
                    noteable_id: noteable.id,
                    noteable_type: type
                };

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                    },
                    body: JSON.stringify(body)
                });
                const note = await response.json();
                
                if (isEdit) {
                    const index = noteable.notes.findIndex(n => n.id === note.id);
                    if (index !== -1) noteable.notes[index] = note;
                } else {
                    if (!noteable.notes) noteable.notes = [];
                    noteable.notes.unshift(note);
                }

                this.newNoteContent = '';
                this.editingNote = null;
            } catch (error) {
                console.error('Error saving note:', error);
            }
        },

        editNote(note) {
            this.editingNote = note;
            this.newNoteContent = note.content;
        },

        async deleteNote(noteId, noteable) {
            if (!confirm('Are you sure you want to delete this note?')) return;
            try {
                const response = await fetch('/api/notes/' + noteId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                    }
                });
                if (response.ok) {
                    noteable.notes = noteable.notes.filter(n => n.id !== noteId);
                }
            } catch (error) {
                console.error('Error deleting note:', error);
            }
        },

        async uploadTranscriptFile(event, meetingId) {
            const file = event.target.files[0];
            if (!file || !meetingId) return;

            this.isUploadingTranscript = true;

            const formData = new FormData();
            formData.append('transcript', file);

            try {
                const response = await fetch(`/api/meetings/${meetingId}/upload-transcript`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                    },
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Transcript upload failed.');
                }

                const updatedMeeting = await response.json();
                const index = this.meetings.findIndex(m => m.id === updatedMeeting.id);
                if (index !== -1) this.meetings[index] = updatedMeeting;
                this.selectedMeetingDetails = updatedMeeting;
                event.target.value = '';
            } catch (error) {
                alert(error.message || 'Transcript upload failed.');
            } finally {
                this.isUploadingTranscript = false;
            }
        },

        async uploadMinutesFile(event, meetingId) {
            const file = event.target.files[0];
            if (!file || !meetingId) return;

            this.isUploadingMinutes = true;

            const formData = new FormData();
            formData.append('minutes', file);

            try {
                const response = await fetch(`/api/meetings/${meetingId}/upload-minutes`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                    },
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Minutes upload failed.');
                }

                const updatedMeeting = await response.json();
                const index = this.meetings.findIndex(m => m.id === updatedMeeting.id);
                if (index !== -1) this.meetings[index] = updatedMeeting;
                this.selectedMeetingDetails = updatedMeeting;
                event.target.value = '';
            } catch (error) {
                alert(error.message || 'Minutes upload failed.');
            } finally {
                this.isUploadingMinutes = false;
            }
        },

        meetingHasDocument(meeting, type) {
            if (!meeting || !Array.isArray(meeting.notes)) return false;
            const keyword = type === 'transcript' ? 'TRANSCRIPT UPLOADED:' : 'MEETING MINUTES UPLOADED:';
            return meeting.notes.some(note => String(note.content || '').includes(keyword));
        },

        async generateMeetingAI(meetingId) {
            alert('Automatic AI transcript is not enabled yet. Please upload transcript or meeting minutes manually.');
        },

        downloadFile(type, format) {
            this.activeDownloadMenu = null;
            
            if (type === 'video' || type === 'audio') {
                const url = type === 'video' ? this.selectedMeetingDetails?.video_url : this.selectedMeetingDetails?.audio_url;
                if (!url) {
                    alert('No ' + type + ' file available for this meeting.');
                    return;
                }
                const link = document.createElement('a');
                link.href = url;
                link.download = `${type}_${this.selectedMeetingDetails?.title?.replace(/\s+/g, '_') || 'recording'}.${format}`;
                link.target = '_blank';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                return;
            }

            const content = this.selectedMeetingDetails?.notes?.find(n => n.content.toLowerCase().includes(type.toLowerCase()))?.content || 'No content found.';
            const filename = `${type}_${this.selectedMeetingDetails?.title?.replace(/\s+/g, '_') || 'doc'}.${format === 'pdf' ? 'pdf' : 'docx'}`;

            if (format === 'pdf') {
                // Simulate PDF by opening a new window with content for printing
                const printWindow = window.open('', '_blank');
                printWindow.document.write('<ht' + 'ml><he' + 'ad><ti' + 'tle>' + filename + '</ti' + 'tle><sty' + 'le>body{font-family:sans-serif;padding:40px;line-height:1.6;}</sty' + 'le></he' + 'ad><bo' + 'dy><h1' + '>' + type.toUpperCase() + '</h1' + '><pre style=\'white-space: pre-wrap;\'>' + content + '</pre></bo' + 'dy></ht' + 'ml>');
                printWindow.document.close();
                printWindow.print();
            } else {
                // Generate a simple Word-compatible blob
                const blob = new Blob([content], { type: 'application/msword' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                link.click();
            }
        }
     }))
});
    function videoPlayer() {
        return {
            playing: false,
            currentTime: 0,
            duration: 0,
            muted: false,
            videoSrc: '',

            init() {
                // React to meeting selection changes from parent scope
                this.$watch('selectedMeetingDetails', (meeting) => {
                    this.videoSrc = meeting?.video_url || '';
                    this.currentTime = 0;
                    this.duration = 0;
                    this.playing = false;
                    if (this.$refs.videoElement) {
                        this.$refs.videoElement.load();
                    }
                });
            },

            togglePlay() {
                let vid = this.$refs.videoElement;
                if (!vid) return;
                if (vid.paused) {
                    vid.play();
                    this.playing = true;
                } else {
                    vid.pause();
                    this.playing = false;
                }
            },
            updateTime() {
                let vid = this.$refs.videoElement;
                if (!vid) return;
                this.currentTime = vid.currentTime;
                // Wait until duration is available
                if (vid.duration && !isNaN(vid.duration)) {
                    this.duration = vid.duration;
                }
            },
            seek(event) {
                let vid = this.$refs.videoElement;
                if (!vid) return;
                let rect = event.currentTarget.getBoundingClientRect();
                let pos = (event.clientX - rect.left) / rect.width;
                vid.currentTime = pos * this.duration;
            },
            skip(seconds) {
                let vid = this.$refs.videoElement;
                if (!vid) return;
                vid.currentTime += seconds;
            },
            toggleMute() {
                let vid = this.$refs.videoElement;
                if (!vid) return;
                vid.muted = !vid.muted;
                this.muted = vid.muted;
            },
            toggleFullScreen() {
                let vid = this.$refs.videoElement;
                if (!vid) return;
                
                if (!document.fullscreenElement) {
                    if (vid.requestFullscreen) {
                        vid.requestFullscreen();
                    } else if (vid.webkitRequestFullscreen) { /* Safari */
                        vid.webkitRequestFullscreen();
                    } else if (vid.msRequestFullscreen) { /* IE11 */
                        vid.msRequestFullscreen();
                    }
                } else {
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    } else if (document.webkitExitFullscreen) { /* Safari */
                        document.webkitExitFullscreen();
                    } else if (document.msExitFullscreen) { /* IE11 */
                        document.msExitFullscreen();
                    }
                }
            },
            formatTime(seconds) {
                if (!seconds || isNaN(seconds)) return '00:00';
                let m = Math.floor(seconds / 60);
                let s = Math.floor(seconds % 60);
                return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
            },
            formatDurationText(seconds) {
                if (!seconds || isNaN(seconds)) return '0 Minutes';
                let m = Math.floor(seconds / 60);
                let s = Math.floor(seconds % 60);
                
                if (m === 0) return `${s} Seconds`;
                if (s === 0) return `${m} Minute${m > 1 ? 's' : ''}`;
                return `${m} Min, ${s} Sec`;
            },
            downloadVideo() {
                if (!this.videoSrc) return;
                const link = document.createElement('a');
                link.href = this.videoSrc;
                link.download = 'meeting_recording.mp4';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }
    }
</script>

{{-- ═══════════════════════════════════════════════════════════════════════
     FLOATING MEETING NOTIFICATION CARD
     Appears when a scheduled meeting's time arrives.
     The "Start Recording" button is a real user-gesture click → valid for getDisplayMedia.
═══════════════════════════════════════════════════════════════════════ --}}
<div x-data
     x-show="$store.app ? false : false"
     style="display:none"></div>

<template x-if="recordingPromptMeeting">
    <div class="fixed bottom-6 right-6 z-[200] w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0">

        {{-- Header --}}
        <div class="bg-gradient-to-r from-[#1d54e2] to-[#3b6ef5] px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                <span class="text-white text-xs font-bold uppercase tracking-widest">Meeting Starting Now</span>
            </div>
            <button @click="dismissRecordingPrompt()" class="text-white/70 hover:text-white transition text-base leading-none">&times;</button>
        </div>

        {{-- Body --}}
        <div class="px-4 py-3">
            <p class="text-sm font-semibold text-gray-900 mb-0.5" x-text="recordingPromptMeeting?.title"></p>
            <p class="text-xs text-gray-500 mb-3">
                <span x-text="recordingPromptMeeting?.date"></span>
                &nbsp;&bull;&nbsp;
                <span x-text="recordingPromptMeeting?.time"></span>
            </p>

            {{-- Feature indicators --}}
            <div class="flex flex-wrap gap-1.5 mb-4">
                <template x-if="recordingPromptMeeting?.has_video">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 text-blue-600 rounded-full text-[10px] font-semibold border border-blue-100">
                        <i class="fas fa-video text-[9px]"></i> Video
                    </span>
                </template>
                <template x-if="recordingPromptMeeting?.has_audio">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 text-blue-600 rounded-full text-[10px] font-semibold border border-blue-100">
                        <i class="fas fa-microphone text-[9px]"></i> Audio
                    </span>
                </template>
                <template x-if="recordingPromptMeeting?.has_transcript">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-50 text-purple-600 rounded-full text-[10px] font-semibold border border-purple-100">
                        <i class="fas fa-align-left text-[9px]"></i> Transcript
                    </span>
                </template>
                <template x-if="recordingPromptMeeting?.has_minutes">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-50 text-purple-600 rounded-full text-[10px] font-semibold border border-purple-100">
                        <i class="fas fa-file-alt text-[9px]"></i> Minutes
                    </span>
                </template>
            </div>

            {{-- Error (if any from previous attempt) --}}
            <div x-show="recordingError" class="mb-3 px-2 py-1.5 bg-red-50 border border-red-100 rounded-lg">
                <p class="text-[10px] text-red-600" x-text="recordingError"></p>
            </div>

            {{-- Action buttons --}}
            <div class="flex gap-2">
                <button @click="confirmStartRecording()"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 bg-[#1d54e2] text-white text-xs font-bold rounded-xl hover:bg-[#1541b0] transition shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-red-400 animate-pulse"></span>
                    Start Recording
                </button>
                <button @click="dismissRecordingPrompt()"
                        class="px-3 py-2 text-xs text-gray-500 hover:text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-xl transition font-medium">
                    Skip
                </button>
            </div>

            <p class="text-[9px] text-gray-400 text-center mt-2">You'll be asked to select your Google Meet tab</p>
        </div>
    </div>
</template>


{{-- ═══════════════════════════════════════════════════════════════════════
     ACTIVE RECORDING STATUS BAR
     Shown while recording/transcribing is in progress.
═══════════════════════════════════════════════════════════════════════ --}}
<template x-if="recordingVideoActive || recordingAudioActive || transcribingActive">
    <div class="fixed bottom-0 left-0 right-0 z-[190] bg-gray-900/95 backdrop-blur-sm px-6 py-3 flex items-center justify-between shadow-2xl"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0">

        <div class="flex items-center gap-4">
            {{-- Live pulse --}}
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span>
                <span class="text-white text-xs font-bold uppercase tracking-wider">Recording</span>
            </div>
            {{-- Active streams --}}
            <div class="flex items-center gap-3 text-[10px] text-gray-400">
                <span x-show="recordingVideoActive" class="flex items-center gap-1">
                    <i class="fas fa-video text-blue-400"></i> Video
                </span>
                <span x-show="recordingAudioActive" class="flex items-center gap-1">
                    <i class="fas fa-microphone text-blue-400"></i> Audio
                </span>
                <span x-show="transcribingActive" class="flex items-center gap-1">
                    <i class="fas fa-align-left text-purple-400"></i> Transcribing
                </span>
            </div>
            {{-- Elapsed time --}}
            <span class="text-gray-300 text-xs font-mono"
                  x-text="`${String(Math.floor(recordingElapsed/3600)).padStart(2,'0')}:${String(Math.floor((recordingElapsed%3600)/60)).padStart(2,'0')}:${String(recordingElapsed%60).padStart(2,'0')}`">
            </span>
        </div>

        {{-- Live transcript preview --}}
        <div x-show="transcribingActive && liveTranscript" class="hidden md:block flex-1 mx-6 px-3 py-1 bg-white/5 rounded-lg overflow-hidden">
            <p class="text-[10px] text-gray-400 truncate" x-text="liveTranscript.slice(-120)"></p>
        </div>

        {{-- Stop button --}}
        <button @click="stopAllAndFinalize()"
                class="inline-flex items-center gap-2 px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg transition">
            <i class="fas fa-stop text-[9px]"></i> Stop & Save
        </button>
    </div>
</template>

@endsection