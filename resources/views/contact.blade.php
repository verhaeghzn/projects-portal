@extends('layouts.app')

@section('title', 'Contact')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-12">
    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-heading text-gray-900 mb-3 sm:mb-4">Contact</h1>
    
    <div class="space-y-6">
        <div class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">About the ME Projects Portal</h2>
            <p class="text-sm sm:text-base text-gray-700 mb-3 sm:mb-4">
                The Department of Mechanical Engineering at Eindhoven University of Technology is organized into three research divisions: <strong>Thermo-Fluids Engineering (TFE)</strong>, <strong>Computational and Experimental Mechanics (CEM)</strong>, and <strong>Dynamical Systems Design (DSD)</strong>. Each division encompasses research sections that drive innovation across energy technology, materials science, manufacturing, microsystems, control systems, dynamics, and robotics.
            </p>
            <p class="text-sm sm:text-base text-gray-700">
                This projects portal serves as a central platform to showcase available research opportunities for students across the department, including bachelor thesis projects and master thesis projects. Browse projects by division or use the filters to find projects that match your interests.
            </p>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">Project Inquiries</h2>
            <p class="text-sm sm:text-base text-gray-700">
                For questions about specific research projects, please visit the project pages and contact the supervisors listed for each project.
            </p>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-5 sm:p-6 lg:p-8">
            <h2 class="text-xl sm:text-2xl font-heading text-gray-900 mb-3 sm:mb-4">Support</h2>
            <p class="text-sm sm:text-base text-gray-700 mb-2">
                For questions about the projects portal—including administrative and staff matters, or technical issues—please contact:
            </p>
            <p class="text-sm sm:text-base text-gray-900 font-medium">
                Andreas Pollet
            </p>
        </div>
    </div>
</div>
@endsection

