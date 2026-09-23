<x-filament-panels::page>
    <style>
        .help-copy p {
            margin: 0 0 0.75rem;
            font-size: 0.875rem;
            line-height: 1.65;
            color: #4b5563;
        }

        .help-copy p:last-child {
            margin-bottom: 0;
        }

        .help-copy a {
            color: #C72026;
            font-weight: 600;
            text-decoration: underline;
        }

        .help-copy p.help-contact {
            margin: 0.25rem 0 0.85rem;
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.5;
            color: #111827;
        }

        .help-topic + .help-topic {
            margin-top: 1.35rem;
            padding-top: 1.35rem;
            border-top: 1px solid #e5e7eb;
        }

        .help-topic h3 {
            margin: 0 0 0.45rem;
            font-size: 0.95rem;
            font-weight: 600;
            line-height: 1.4;
            color: #111827;
        }

        .help-copy p.help-url {
            margin: 0.85rem 0 0;
            padding: 0.65rem 0.8rem;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            line-height: 1.4;
            color: inherit;
        }

        .help-url a {
            color: #C72026;
            font-weight: 600;
            text-decoration: none;
            word-break: break-all;
        }
    </style>

    <x-filament::section class="mb-5">
        <x-slot name="heading">
            Support
        </x-slot>
        <x-slot name="description">
            For questions about the portal—administrative, staff, or technical
        </x-slot>
        <div class="help-copy">
            <p>
                For questions about the projects portal, including administrative and staff matters, or technical issues, please contact:
            </p>
            <p class="help-contact">
                Andreas Pollet (<a href="mailto:a.m.a.o.pollet@tue.nl">a.m.a.o.pollet@tue.nl</a>) — main support contact
            </p>
            <p>
                The portal is managed by Andreas Pollet, Joris Remmers and Bart Verhaegh.
            </p>
        </div>
    </x-filament::section>

    @if($guide)
        <x-filament::section class="mb-5">
            <x-slot name="heading">
                Using the admin panel
            </x-slot>
            <x-slot name="description">
                How to publish projects and keep them up to date
            </x-slot>
            <div class="help-copy">
                @foreach($guide->features() as $feature)
                    <div class="help-topic">
                        <h3>{{ $feature['title'] }}</h3>
                        @foreach($feature['body'] as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                        @if(! empty($feature['url']))
                            <p class="help-url">
                                <a href="{{ $feature['url'] }}">{{ $feature['url'] }}</a>
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    <x-filament::section>
        <x-slot name="heading">
            About the Portal
        </x-slot>
        <x-slot name="description">
            Information about the ME Projects Portal
        </x-slot>
        <div class="help-copy">
            <p>
                The Department of Mechanical Engineering at Eindhoven University of Technology is organized into three research divisions: <strong>Thermo-Fluids Engineering (TFE)</strong>, <strong>Computational and Experimental Mechanics (CEM)</strong>, and <strong>Dynamical Systems Design (DSD)</strong>. Each division encompasses research sections that drive innovation across energy technology, materials science, manufacturing, microsystems, control systems, dynamics, and robotics.
            </p>
            <p>
                This projects portal serves as a central platform to showcase available research opportunities for students across the department, including bachelor thesis projects and master thesis projects. Students can browse projects by division or use filters to find projects that match their interests.
            </p>
        </div>
    </x-filament::section>
</x-filament-panels::page>
